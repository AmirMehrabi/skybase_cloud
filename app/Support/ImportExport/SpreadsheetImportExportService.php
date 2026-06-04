<?php

namespace App\Support\ImportExport;

use App\Models\Customer;
use App\Models\ImportExportRun;
use App\Models\ImportExportRunRow;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionIpRouteSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class SpreadsheetImportExportService
{
    public function export(ImportExportRun $run): void
    {
        $headings = ImportExportSchema::headings($run->module);
        $rows = match ($run->module) {
            ImportExportSchema::MODULE_PLANS => $this->planExportRows($run->filters ?? []),
            ImportExportSchema::MODULE_SUBSCRIPTIONS => $this->subscriptionExportRows($run),
            default => [],
        };

        $path = ImportExportSchema::basePath($run->tenant_id, $run->id).'/'.ImportExportSchema::exportFilename($run->module, $run->id);

        $this->writeSpreadsheet($path, $headings, $rows);

        $run->markCompleted([
            'file_path' => $path,
            'total_rows' => count($rows),
            'processed_rows' => count($rows),
            'summary' => [
                'columns' => $headings,
                'message' => 'Export completed successfully.',
            ],
        ]);
    }

    public function import(ImportExportRun $run): void
    {
        abort_unless(Storage::disk($run->disk)->exists($run->file_path), 404, 'Imported file is missing from storage.');
        $path = Storage::disk($run->disk)->path($run->file_path);
        $rows = $this->readSpreadsheet($path);
        $counters = [
            'total_rows' => count($rows),
            'processed_rows' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'failed_count' => 0,
        ];

        foreach ($rows as $rowNumber => $row) {
            $counters['processed_rows']++;

            try {
                $result = match ($run->module) {
                    ImportExportSchema::MODULE_PLANS => $this->importPlanRow($row),
                    ImportExportSchema::MODULE_SUBSCRIPTIONS => $this->importSubscriptionRow($run, $row),
                    default => ['status' => 'failed', 'identifier' => null, 'action' => null, 'message' => 'Unsupported import module.'],
                };
            } catch (Throwable $exception) {
                $result = [
                    'status' => 'failed',
                    'identifier' => $row['plan_name'] ?? $row['plan_internal_name'] ?? $row['subscription_code'] ?? $row['customer_code'] ?? null,
                    'action' => null,
                    'message' => $exception->getMessage(),
                ];
            }

            $this->recordRow($run, $rowNumber, $result, $row);

            match ($result['status']) {
                'created' => $counters['created_count']++,
                'updated' => $counters['updated_count']++,
                'skipped' => $counters['skipped_count']++,
                default => $counters['failed_count']++,
            };

            if ($counters['processed_rows'] % 25 === 0) {
                $run->update($counters);
            }
        }

        $run->markCompleted([
            ...$counters,
            'summary' => [
                'message' => $counters['failed_count'] > 0
                    ? 'Import completed with row-level failures.'
                    : 'Import completed successfully.',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<int, mixed>>
     */
    protected function planExportRows(array $filters): array
    {
        return Plan::query()
            ->filter($filters)
            ->ordered()
            ->get()
            ->map(fn (Plan $plan): array => [
                $plan->name,
                $plan->internal_name,
                $plan->description,
                $plan->status,
                $plan->visibility,
                $plan->type,
                $plan->category,
                $plan->download_speed,
                $plan->upload_speed,
                $plan->burst_download,
                $plan->burst_upload,
                $plan->bandwidth_unit,
                $plan->data_limit,
                $plan->data_unit,
                $this->boolForExport($plan->unlimited),
                $plan->price,
                $plan->currency,
                $plan->billing_cycle,
                $plan->grace_period_days,
                $plan->setup_fee,
                $plan->tax_profile,
                $plan->router_profile,
                $plan->ip_pool,
                $plan->priority,
                $this->boolForExport($plan->contract_required),
                $plan->contract_duration,
                $plan->available_from?->format('Y-m-d'),
                $plan->available_to?->format('Y-m-d'),
                $plan->notes,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<int, mixed>>
     */
    protected function subscriptionExportRows(ImportExportRun $run): array
    {
        $filters = $run->filters ?? [];

        return Subscription::query()
            ->where('tenant_id', $run->tenant_id)
            ->filter($filters)
            ->with(['customer', 'plan', 'router'])
            ->latest()
            ->get()
            ->map(function (Subscription $subscription): array {
                $customer = $subscription->customer;

                return [
                    $customer?->customer_code,
                    $customer?->customer_type,
                    $customer?->name,
                    $customer?->first_name,
                    $customer?->last_name,
                    $customer?->company_name,
                    $customer?->national_id,
                    $customer?->email,
                    $customer?->phone,
                    $customer?->mobile,
                    $customer?->whatsapp,
                    $customer?->address_line1,
                    $customer?->address_line2,
                    $customer?->city,
                    $customer?->state,
                    $customer?->postal_code,
                    $customer?->country,
                    $customer?->status,
                    $customer?->billing_type,
                    $this->boolForExport($customer?->billing_enabled),
                    $customer?->balance,
                    $customer?->credit_limit,
                    $this->boolForExport($customer?->tax_exempt),
                    $subscription->subscription_code,
                    $subscription->name,
                    $subscription->service_type,
                    $subscription->plan?->name,
                    $subscription->router?->name,
                    $subscription->site,
                    $subscription->connection_type,
                    $subscription->ip_address,
                    $subscription->mac_address,
                    $subscription->ip_management,
                    $subscription->pppoe_username,
                    $subscription->pppoe_password,
                    $subscription->base_price,
                    $subscription->discount_amount,
                    $subscription->discount_type,
                    $subscription->tax_amount,
                    $subscription->total_price,
                    $subscription->billing_cycle,
                    $this->boolForExport($subscription->billing_enabled),
                    $subscription->grace_period_days,
                    $subscription->next_billing_date?->format('Y-m-d'),
                    $subscription->status,
                    $subscription->start_date?->format('Y-m-d H:i:s'),
                    $subscription->end_date?->format('Y-m-d H:i:s'),
                    $subscription->activation_date?->format('Y-m-d H:i:s'),
                    $subscription->suspended_at?->format('Y-m-d H:i:s'),
                    $subscription->cancelled_at?->format('Y-m-d H:i:s'),
                    $subscription->notes,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{status: string, identifier: ?string, action: ?string, message: string}
     */
    protected function importPlanRow(array $row): array
    {
        $data = $this->normalizePlanRow($row);
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'internal_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
            'visibility' => ['required', Rule::in(['public', 'private', 'hidden'])],
            'type' => ['required', Rule::in(['pppoe', 'hotspot', 'static', 'dhcp', 'fiber', 'wireless'])],
            'category' => ['nullable', 'string', 'max:255'],
            'download_speed' => ['required', 'integer', 'min:0'],
            'upload_speed' => ['required', 'integer', 'min:0'],
            'burst_download' => ['nullable', 'integer', 'min:0'],
            'burst_upload' => ['nullable', 'integer', 'min:0'],
            'bandwidth_unit' => ['required', Rule::in(['Kbps', 'Mbps', 'Gbps'])],
            'data_limit' => ['nullable', 'integer', 'min:0'],
            'data_unit' => ['required', Rule::in(['MB', 'GB', 'TB'])],
            'unlimited' => ['boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'setup_fee' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'contract_required' => ['boolean'],
            'contract_duration' => ['nullable', 'integer', 'min:1'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->failedResult($data['internal_name'] ?? null, $validator->errors()->first());
        }

        $plan = Plan::query()->where('internal_name', $data['internal_name'])->first();
        $action = $plan ? 'updated' : 'created';
        $plan ??= new Plan;
        $plan->fill($data);
        $plan->save();

        return [
            'status' => $action,
            'identifier' => $plan->internal_name,
            'action' => $action,
            'message' => "Plan {$action}.",
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{status: string, identifier: ?string, action: ?string, message: string}
     */
    protected function importSubscriptionRow(ImportExportRun $run, array $row): array
    {
        $data = $this->normalizeSubscriptionRow($row);
        $ipAddresses = $this->parsedImportIpAddresses($data['ip_address'] ?? null);

        if ($ipAddresses['error'] !== null) {
            return $this->failedResult($data['subscription_code'] ?? $data['customer_code'] ?? null, $ipAddresses['error']);
        }

        $primaryIpAddress = array_shift($ipAddresses['addresses']);
        $routeIpAddresses = $ipAddresses['addresses'];
        $primaryIpPool = $primaryIpAddress ? $this->resolveIpPoolForAddress($run, $primaryIpAddress) : null;

        if ($routeIpAddresses !== [] && ! $primaryIpPool) {
            return $this->failedResult($data['subscription_code'] ?? $data['customer_code'] ?? null, 'A primary IPAM pool could not be resolved for the imported IP address list.');
        }

        if ($routeIpAddresses !== []) {
            $data['ip_management'] = 'system';
        }

        $data['ip_address'] = $primaryIpAddress;
        $data['ip_pool_id'] = $primaryIpPool?->id;
        $validator = Validator::make($data, [
            'customer_code' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['nullable', Rule::in(['individual', 'business'])],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'customer_status' => ['nullable', Rule::in(['pending', 'active', 'inactive', 'suspended'])],
            'billing_type' => ['nullable', Rule::in(['prepaid', 'postpaid'])],
            'plan_name' => ['required', 'string', 'max:255'],
            'router_name' => ['nullable', 'string', 'max:255'],
            'subscription_code' => ['nullable', 'string', 'max:255'],
            'subscription_name' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', Rule::in(['hotspot', 'pppoe', 'vpn'])],
            'connection_type' => ['nullable', Rule::in(['pppoe', 'dhcp', 'static'])],
            'ip_address' => ['nullable', 'string', 'max:255'],
            'mac_address' => ['nullable', 'string', 'max:17'],
            'ip_management' => ['nullable', Rule::in(['system', 'router'])],
            'pppoe_username' => ['nullable', 'string', 'max:255'],
            'pppoe_password' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['nullable', Rule::in(['none', 'fixed', 'percentage'])],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'status' => ['nullable', Rule::in(['pending', 'active', 'suspended', 'cancelled'])],
            'next_billing_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'activation_date' => ['nullable', 'date'],
            'suspended_at' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->failedResult($data['subscription_code'] ?? $data['customer_code'] ?? null, $validator->errors()->first());
        }

        if (blank($data['customer_code'] ?? null) && blank($data['customer_name'] ?? null) && blank($data['first_name'] ?? null) && blank($data['company_name'] ?? null)) {
            return $this->failedResult($data['subscription_code'] ?? null, 'Customer identity is required.');
        }

        $plan = $this->findPlanByName($data['plan_name']);
        if (! $plan) {
            return $this->failedResult($data['subscription_code'] ?? null, 'Plan name was not found or is ambiguous.');
        }

        $router = null;
        if (filled($data['router_name'] ?? null)) {
            $router = Router::query()
                ->where('tenant_id', $run->tenant_id)
                ->where('name', $data['router_name'])
                ->first();

            if (! $router) {
                return $this->failedResult($data['subscription_code'] ?? null, 'Router name was not found in this tenant.');
            }
        }

        $existingSubscription = filled($data['subscription_code'] ?? null)
            ? Subscription::query()->where('tenant_id', $run->tenant_id)->where('subscription_code', $data['subscription_code'])->first()
            : null;

        if (filled($data['pppoe_username'] ?? null)) {
            $duplicateUsername = Subscription::query()
                ->where('tenant_id', $run->tenant_id)
                ->where('pppoe_username', $data['pppoe_username'])
                ->when($existingSubscription, fn (Builder $query) => $query->whereKeyNot($existingSubscription->id))
                ->exists();

            if ($duplicateUsername) {
                return $this->failedResult($data['subscription_code'] ?? null, 'PPPoE username already exists in this tenant.');
            }
        }

        $transactionResult = DB::transaction(function () use ($run, $data, $plan, $router, $existingSubscription, $routeIpAddresses, $primaryIpAddress): array {
            $customer = $this->resolveCustomerForImport($run, $data);
            $customerAction = $customer->exists ? 'updated' : 'created';
            $customer->fill($this->customerAttributes($data, $customer->exists ? $customer : null));
            $customer->tenant_id = $run->tenant_id;
            $customer->save();

            $subscriptionAction = $existingSubscription ? 'updated' : 'created';
            $subscription = $existingSubscription ?? new Subscription(['tenant_id' => $run->tenant_id]);
            $subscription->fill($this->subscriptionAttributes($data, $customer, $plan, $router, $existingSubscription, $primaryIpAddress));
            $subscription->tenant_id = $run->tenant_id;
            $subscription->customer_id = $customer->id;
            $subscription->plan_id = $plan->id;
            $subscription->router_id = $router?->id;
            $subscription->save();

            if ($subscription->isSystemManagedIp() && filled($data['ip_address'] ?? null)) {
                $assignedPrimaryIp = $subscription->updateIpAddress($data['ip_address']);

                if (! $assignedPrimaryIp) {
                    throw new \RuntimeException('The primary IP address could not be assigned from the resolved IPAM pool.');
                }
            }

            $this->replaceImportedSubscriptionIpRoutes($subscription->fresh(['ipRoutes']), $customer, $routeIpAddresses);

            app(RadiusProvisioningService::class)->syncSubscriptionsForCustomer($customer->fresh());

            return [
                'subscription' => $subscription->fresh(['router', 'ipRoutes']),
                'status' => $subscriptionAction === 'created' || $customerAction === 'created' ? 'created' : 'updated',
                'identifier' => $subscription->subscription_code,
                'action' => "{$customerAction} customer, {$subscriptionAction} subscription",
                'message' => "Customer {$customerAction}; subscription {$subscriptionAction}.",
            ];
        });

        app(SubscriptionIpRouteSyncService::class)->syncRoutes($transactionResult['subscription']);

        unset($transactionResult['subscription']);

        return $transactionResult;
    }

    /**
     * @param  list<string>  $headings
     * @param  list<array<int, mixed>>  $rows
     */
    protected function writeSpreadsheet(string $path, array $headings, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('template');

        foreach ($headings as $columnIndex => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).'1', $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 2), $value);
            }
        }

        foreach (range(1, count($headings)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        Storage::disk('imports')->makeDirectory(dirname($path));
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('imports')->path($path));
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $headings = [];
        $rows = [];

        for ($column = 1; $column <= $highestColumn; $column++) {
            $headings[$column] = str((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column).'1')->getValue())->trim()->snake()->value();
        }

        for ($row = 2; $row <= $highestRow; $row++) {
            $payload = [];
            $hasValue = false;

            foreach ($headings as $column => $heading) {
                if ($heading === '') {
                    continue;
                }

                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row);
                $value = $cell->getValue();
                $payload[$heading] = is_string($value) ? trim($value) : $value;
                $hasValue = $hasValue || filled($payload[$heading]);
            }

            if ($hasValue) {
                $rows[$row] = $payload;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizePlanRow(array $row): array
    {
        $name = $this->stringOrNull($row['name'] ?? null);
        $internalName = $this->stringOrNull($row['internal_name'] ?? null);

        return [
            'name' => $name,
            'internal_name' => $internalName ?: ($name ? Str::slug($name) : null),
            'description' => $this->stringOrNull($row['description'] ?? null),
            'status' => $this->stringOrDefault($row['status'] ?? null, 'active'),
            'visibility' => $this->stringOrDefault($row['visibility'] ?? null, 'public'),
            'type' => $this->stringOrDefault($row['type'] ?? null, 'pppoe'),
            'category' => $this->stringOrNull($row['category'] ?? null),
            'download_speed' => $this->integerOrDefault($row['download_speed'] ?? null, 0),
            'upload_speed' => $this->integerOrDefault($row['upload_speed'] ?? null, 0),
            'burst_download' => $this->integerOrDefault($row['burst_download'] ?? null, 0),
            'burst_upload' => $this->integerOrDefault($row['burst_upload'] ?? null, 0),
            'bandwidth_unit' => $this->stringOrDefault($row['bandwidth_unit'] ?? null, 'Mbps'),
            'data_limit' => $this->integerOrNull($row['data_limit'] ?? null),
            'data_unit' => $this->stringOrDefault($row['data_unit'] ?? null, 'GB'),
            'unlimited' => $this->boolValue($row['unlimited'] ?? false),
            'price' => $this->decimalOrDefault($row['price'] ?? null, 0),
            'currency' => $this->stringOrDefault($row['currency'] ?? null, 'USD'),
            'billing_cycle' => $this->stringOrDefault($row['billing_cycle'] ?? null, 'monthly'),
            'grace_period_days' => $this->integerOrDefault($row['grace_period_days'] ?? null, 7),
            'setup_fee' => $this->decimalOrDefault($row['setup_fee'] ?? null, 0),
            'tax_profile' => $this->stringOrNull($row['tax_profile'] ?? null),
            'router_profile' => $this->stringOrNull($row['router_profile'] ?? null),
            'ip_pool' => $this->stringOrNull($row['ip_pool'] ?? null),
            'priority' => $this->integerOrDefault($row['priority'] ?? null, 5),
            'contract_required' => $this->boolValue($row['contract_required'] ?? false),
            'contract_duration' => $this->integerOrNull($row['contract_duration'] ?? null),
            'available_from' => $this->dateOrNull($row['available_from'] ?? null),
            'available_to' => $this->dateOrNull($row['available_to'] ?? null),
            'notes' => $this->stringOrNull($row['notes'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeSubscriptionRow(array $row): array
    {
        $normalized = [];

        foreach (ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS) as $heading) {
            $normalized[$heading] = $this->stringOrNull($row[$heading] ?? null);
        }

        $normalized['plan_name'] = $this->stringOrNull($row['plan_name'] ?? $row['plan_internal_name'] ?? null);

        foreach (['customer_billing_enabled', 'tax_exempt', 'billing_enabled'] as $field) {
            $normalized[$field] = filled($row[$field] ?? null) ? $this->boolValue($row[$field]) : null;
        }

        foreach (['balance', 'credit_limit', 'base_price', 'discount_amount', 'tax_amount', 'total_price'] as $field) {
            $normalized[$field] = $this->decimalOrNull($row[$field] ?? null);
        }

        $normalized['grace_period_days'] = $this->integerOrNull($row['grace_period_days'] ?? null);
        $normalized['next_billing_date'] = $this->dateOrNull($row['next_billing_date'] ?? null);

        foreach (['start_date', 'end_date', 'activation_date', 'suspended_at', 'cancelled_at'] as $field) {
            $normalized[$field] = $this->dateTimeOrNull($row[$field] ?? null);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function customerAttributes(array $data, ?Customer $existingCustomer = null): array
    {
        $name = $this->customerDisplayName($data, $existingCustomer);

        return [
            'customer_code' => $data['customer_code'] ?: $existingCustomer?->customer_code,
            'customer_type' => $data['customer_type'] ?: $existingCustomer?->customer_type ?: (filled($data['company_name']) ? 'business' : 'individual'),
            'first_name' => $this->blankToExisting($data['first_name'], $existingCustomer?->first_name),
            'last_name' => $this->blankToExisting($data['last_name'], $existingCustomer?->last_name),
            'company_name' => $this->blankToExisting($data['company_name'], $existingCustomer?->company_name),
            'name' => $name,
            'national_id' => $this->blankToExisting($data['national_id'], $existingCustomer?->national_id),
            'email' => $this->blankToExisting($data['email'], $existingCustomer?->email),
            'phone' => $this->blankToExisting($data['phone'], $existingCustomer?->phone),
            'mobile' => $this->blankToExisting($data['mobile'], $existingCustomer?->mobile),
            'whatsapp' => $this->blankToExisting($data['whatsapp'], $existingCustomer?->whatsapp),
            'address_line1' => $this->blankToExisting($data['address_line1'], $existingCustomer?->address_line1),
            'address_line2' => $this->blankToExisting($data['address_line2'], $existingCustomer?->address_line2),
            'city' => $this->blankToExisting($data['city'], $existingCustomer?->city),
            'state' => $this->blankToExisting($data['state'], $existingCustomer?->state),
            'postal_code' => $this->blankToExisting($data['postal_code'], $existingCustomer?->postal_code),
            'country' => $this->blankToExisting($data['country'], $existingCustomer?->country),
            'status' => $data['customer_status'] ?: $existingCustomer?->status ?: 'active',
            'billing_type' => $data['billing_type'] ?: $existingCustomer?->billing_type ?: 'prepaid',
            'billing_enabled' => $data['customer_billing_enabled'] ?? $existingCustomer?->billing_enabled ?? true,
            'billing_disabled_at' => $data['customer_billing_enabled'] === null
                ? $existingCustomer?->billing_disabled_at
                : ($data['customer_billing_enabled'] ? null : now()),
            'balance' => $data['balance'] ?? $existingCustomer?->balance ?? 0,
            'credit_limit' => $data['credit_limit'] ?? $existingCustomer?->credit_limit ?? 0,
            'tax_exempt' => $data['tax_exempt'] ?? $existingCustomer?->tax_exempt ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function subscriptionAttributes(array $data, Customer $customer, Plan $plan, ?Router $router, ?Subscription $existingSubscription = null, ?string $primaryIpAddress = null): array
    {
        $basePrice = $data['base_price'] ?? $plan->price;
        $billingCycle = $data['billing_cycle'] ?: $plan->billing_cycle;
        $gracePeriodDays = $data['grace_period_days'] ?? $plan->grace_period_days;
        $isSystemManagedIp = ($data['ip_management'] ?? $existingSubscription?->ip_management) === 'system';

        return [
            'subscription_code' => $data['subscription_code'] ?: $existingSubscription?->subscription_code ?: null,
            'name' => $data['subscription_name'] ?: $existingSubscription?->name ?: $customer->full_name,
            'service_type' => $data['service_type'] ?: $existingSubscription?->service_type ?: 'hotspot',
            'site' => $data['site'] ?: $existingSubscription?->site ?: $router?->site,
            'connection_type' => $data['connection_type'] ?: $existingSubscription?->connection_type ?: 'pppoe',
            'ip_address' => $isSystemManagedIp && filled($primaryIpAddress)
                ? null
                : $this->blankToExisting($data['ip_address'], $existingSubscription?->ip_address),
            'ip_pool_id' => $data['ip_pool_id'] ?? $existingSubscription?->ip_pool_id,
            'mac_address' => $this->blankToExisting($data['mac_address'], $existingSubscription?->mac_address),
            'ip_management' => $data['ip_management'] ?: $existingSubscription?->ip_management,
            'pppoe_username' => $data['pppoe_username'] ?: $existingSubscription?->pppoe_username,
            'pppoe_password' => $data['pppoe_password'] ?: $existingSubscription?->pppoe_password,
            'base_price' => $basePrice,
            'discount_amount' => $data['discount_amount'] ?? $existingSubscription?->discount_amount ?? 0,
            'discount_type' => $data['discount_type'] ?: $existingSubscription?->discount_type ?: 'none',
            'tax_amount' => $data['tax_amount'] ?? $existingSubscription?->tax_amount ?? 0,
            'total_price' => $data['total_price'] ?? $plan->price,
            'billing_cycle' => $billingCycle,
            'billing_enabled' => $data['billing_enabled'] ?? $existingSubscription?->billing_enabled ?? true,
            'grace_period_days' => $gracePeriodDays,
            'next_billing_date' => $data['next_billing_date'],
            'billing_disabled_at' => $data['billing_enabled'] === null
                ? $existingSubscription?->billing_disabled_at
                : ($data['billing_enabled'] ? null : now()),
            'status' => $data['status'] ?: $existingSubscription?->status ?: 'pending',
            'start_date' => $data['start_date'] ?? $existingSubscription?->start_date,
            'end_date' => $data['end_date'] ?? $existingSubscription?->end_date,
            'activation_date' => $data['activation_date'] ?? $existingSubscription?->activation_date,
            'suspended_at' => $data['suspended_at'] ?? $existingSubscription?->suspended_at,
            'cancelled_at' => $data['cancelled_at'] ?? $existingSubscription?->cancelled_at,
            'notes' => $data['notes'] ?? $existingSubscription?->notes,
        ];
    }

    protected function resolveCustomerForImport(ImportExportRun $run, array $data): Customer
    {
        if (filled($data['customer_code'] ?? null)) {
            $customer = Customer::query()
                ->where('tenant_id', $run->tenant_id)
                ->where('customer_code', $data['customer_code'])
                ->first();

            if ($customer) {
                return $customer;
            }
        }

        if ($this->canMatchCustomerNaturally($data)) {
            $customer = Customer::query()
                ->where('tenant_id', $run->tenant_id)
                ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower(trim((string) $data['first_name']))])
                ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower(trim((string) $data['last_name']))])
                ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim((string) $data['email']))])
                ->first();

            if ($customer) {
                return $customer;
            }
        }

        return new Customer(['tenant_id' => $run->tenant_id]);
    }

    protected function findPlanByName(string $planName): ?Plan
    {
        $normalizedName = mb_strtolower(trim($planName));

        $plans = Plan::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
            ->get();

        if ($plans->count() !== 1) {
            return null;
        }

        return $plans->first();
    }

    protected function customerDisplayName(array $data, ?Customer $existingCustomer = null): string
    {
        $derivedName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        return $data['customer_name']
            ?: $derivedName
            ?: $data['company_name']
            ?: $existingCustomer?->name
            ?: '';
    }

    protected function canMatchCustomerNaturally(array $data): bool
    {
        return filled($data['first_name'] ?? null)
            && filled($data['last_name'] ?? null)
            && filled($data['email'] ?? null);
    }

    protected function blankToExisting(mixed $value, mixed $existingValue): mixed
    {
        return filled($value) ? $value : $existingValue;
    }

    /**
     * @param  array{status: string, identifier: ?string, action: ?string, message: string}  $result
     * @param  array<string, mixed>  $payload
     */
    protected function recordRow(ImportExportRun $run, int $rowNumber, array $result, array $payload): void
    {
        ImportExportRunRow::query()->create([
            'import_export_run_id' => $run->id,
            'row_number' => $rowNumber,
            'status' => $result['status'],
            'identifier' => $result['identifier'],
            'action' => $result['action'],
            'message' => $result['message'],
            'payload' => $payload,
        ]);
    }

    /**
     * @return array{status: string, identifier: ?string, action: ?string, message: string}
     */
    protected function failedResult(?string $identifier, string $message): array
    {
        return [
            'status' => 'failed',
            'identifier' => $identifier,
            'action' => null,
            'message' => $message,
        ];
    }

    protected function boolForExport(mixed $value): string
    {
        return $value ? 'yes' : 'no';
    }

    protected function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'on'], true);
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return filled($value) ? trim((string) $value) : null;
    }

    protected function stringOrDefault(mixed $value, string $default): string
    {
        return filled($value) ? trim((string) $value) : $default;
    }

    protected function integerOrDefault(mixed $value, int $default): int
    {
        return filled($value) ? (int) $value : $default;
    }

    protected function integerOrNull(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    protected function decimalOrDefault(mixed $value, float|int $default): float|int
    {
        return filled($value) ? (float) $value : $default;
    }

    protected function decimalOrNull(mixed $value): ?float
    {
        return filled($value) ? (float) $value : null;
    }

    protected function dateOrNull(mixed $value): ?string
    {
        return $this->dateTimeOrNull($value, 'Y-m-d');
    }

    protected function dateTimeOrNull(mixed $value, string $format = 'Y-m-d H:i:s'): ?string
    {
        if (! filled($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return SpreadsheetDate::excelToDateTimeObject((float) $value)->format($format);
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : date($format, $timestamp);
    }

    /**
     * @return array{addresses: list<string>, error: ?string}
     */
    protected function parsedImportIpAddresses(mixed $value): array
    {
        if (! filled($value)) {
            return ['addresses' => [], 'error' => null];
        }

        $addresses = collect(explode(',', (string) $value))
            ->map(fn (string $address): string => $this->normalizeImportedIpAddress($address))
            ->filter()
            ->values()
            ->all();

        $uniqueAddresses = array_values(array_unique($addresses));
        if ($uniqueAddresses !== $addresses) {
            return ['addresses' => [], 'error' => 'Duplicate IP addresses are not allowed in the ip_address field.'];
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return ['addresses' => [], 'error' => "The IP address {$address} is not a valid IPv4 address."];
            }
        }

        return ['addresses' => $addresses, 'error' => null];
    }

    protected function normalizeImportedIpAddress(string $address): string
    {
        $address = trim($address);

        if ($address === '') {
            return '';
        }

        return trim(Str::before($address, '/'));
    }

    protected function resolveIpPoolForAddress(ImportExportRun $run, string $ipAddress): ?IpPool
    {
        return IpPool::query()
            ->where('tenant_id', $run->tenant_id)
            ->get()
            ->sortByDesc(fn (IpPool $pool): int => (($pool->status === 'active' ? 1 : 0) * 1000) + (int) $pool->cidr)
            ->first(fn (IpPool $pool): bool => $this->isIpInPoolRange($ipAddress, $pool));
    }

    protected function replaceImportedSubscriptionIpRoutes(Subscription $subscription, Customer $customer, array $routeIpAddresses): void
    {
        $subscription->loadMissing(['ipRoutes']);

        foreach ($subscription->ipRoutes as $route) {
            $this->releaseImportedRouteIpAddress($route);
            $route->delete();
        }

        foreach (array_values($routeIpAddresses) as $routeIpAddress) {
            $ipPool = $this->resolveIpPoolForAddressForSubscription($subscription, $routeIpAddress);
            if (! $ipPool) {
                throw new \RuntimeException("Unable to resolve an IPAM pool for imported route IP {$routeIpAddress}.");
            }

            $ipAddress = $this->resolveImportedRouteIpAddress($subscription, $ipPool, $routeIpAddress);
            if (! $ipAddress) {
                throw new \RuntimeException("Unable to resolve an IP address record for imported route IP {$routeIpAddress}.");
            }

            $route = SubscriptionIpRoute::create([
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'ip_pool_id' => $ipPool->id,
                'ip_address_id' => $ipAddress->id,
                'ip_address' => $routeIpAddress,
                'cidr' => 32,
            ]);

            $route->forceFill([
                'routeros_comment' => $route->routerOsComment(),
            ])->save();

            $this->assignImportedRouteIpAddress($ipAddress, $customer, $subscription, $route);
        }
    }

    protected function resolveIpPoolForAddressForSubscription(Subscription $subscription, string $ipAddress): ?IpPool
    {
        return IpPool::query()
            ->where('tenant_id', $subscription->tenant_id)
            ->get()
            ->sortByDesc(fn (IpPool $pool): int => (($pool->status === 'active' ? 1 : 0) * 1000) + (int) $pool->cidr)
            ->first(fn (IpPool $pool): bool => $this->isIpInPoolRange($ipAddress, $pool));
    }

    protected function resolveImportedRouteIpAddress(Subscription $subscription, IpPool $ipPool, string $ipAddress): ?IpAddress
    {
        $ipAddressRecord = IpAddress::query()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('ip_pool_id', $ipPool->id)
            ->where('ip_address', $ipAddress)
            ->first();

        if (! $ipAddressRecord) {
            return null;
        }

        if (! $ipAddressRecord->isAvailable()) {
            return null;
        }

        return $ipAddressRecord;
    }

    protected function assignImportedRouteIpAddress(IpAddress $ipAddress, Customer $customer, Subscription $subscription, SubscriptionIpRoute $route): void
    {
        $ipAddress->forceFill([
            'status' => 'assigned',
            'customer_id' => $customer->id,
            'mac_address' => null,
            'subscription_code' => null,
            'assigned_at' => now(),
            'released_at' => null,
            'notes' => 'Subscription IP route '.$subscription->subscription_code,
            'metadata' => [
                'purpose' => 'subscription_ip_route',
                'subscription_id' => $subscription->id,
                'subscription_ip_route_id' => $route->id,
            ],
        ])->save();
    }

    protected function releaseImportedRouteIpAddress(SubscriptionIpRoute $route): void
    {
        if (! $route->ip_address_id) {
            return;
        }

        $ipAddress = IpAddress::query()->find($route->ip_address_id);

        if (! $ipAddress) {
            return;
        }

        $ipAddress->release();
        $ipAddress->forceFill([
            'notes' => null,
            'metadata' => null,
        ])->save();
    }

    protected function isIpInPoolRange(string $ipAddress, IpPool $ipPool): bool
    {
        $ipNum = ip2long($ipAddress);
        $networkNum = ip2long($ipPool->network_address);

        if ($ipNum === false || $networkNum === false) {
            return false;
        }

        $cidr = (int) $ipPool->cidr;
        $mask = $cidr === 0 ? 0 : ~0 << (32 - $cidr);

        return ($ipNum & $mask) === ($networkNum & $mask);
    }
}
