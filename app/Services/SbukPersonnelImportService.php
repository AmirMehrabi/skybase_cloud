<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\SbukPersonnelIdentity;
use App\Models\SbukSyncRun;
use App\Models\SbukSyncRunRecord;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SbukPersonnelImportService
{
    /** @return array{records: array<int, array<string, mixed>>, summary: array<string, int|null>} */
    public function preview(array $rawRecords, string $tenantId, int $planId, int $routerId, int $ipPoolId): array
    {
        $normalized = $this->normalize($rawRecords);
        $summary = [
            'total_records' => count($rawRecords),
            'valid_records' => 0,
            'new_users' => 0,
            'existing_users' => 0,
            'new_subscriptions' => 0,
            'already_subscribed' => 0,
            'invalid_or_conflicting' => 0,
            'available_ip_addresses' => null,
        ];
        $seen = [];
        foreach ($normalized as &$person) {
            if ($person['national_id'] !== null) {
                $hash = $this->identityHash($person['national_id']);
                if (isset($seen[$hash])) {
                    $person['error'] = 'Duplicate national ID in ERP response.';
                }
                $seen[$hash] = true;
            }
            if ($person['error'] !== null) {
                $summary['invalid_or_conflicting']++;

                continue;
            }
            $summary['valid_records']++;
            $matches = $this->customersForIdentity($tenantId, $person['national_id']);
            if ($matches->count() > 1) {
                $person['error'] = 'National ID matches multiple local customer accounts.';
                $summary['invalid_or_conflicting']++;
                $summary['valid_records']--;

                continue;
            }
            $mappedCustomer = SbukPersonnelIdentity::query()->where('tenant_id', $tenantId)
                ->where('identity_hash', $this->identityHash($person['national_id']))->first()?->customer;
            $person['customer_id'] = $mappedCustomer?->id ?? $matches->first()?->id;
            if ($person['customer_id']) {
                $summary['existing_users']++;
            } else {
                $summary['new_users']++;
            }
            $subscription = $person['customer_id']
                ? Subscription::query()->where('tenant_id', $tenantId)->where('customer_id', $person['customer_id'])
                    ->where('plan_id', $planId)->whereIn('status', ['pending', 'active'])->first()
                : null;
            $managedSubscription = $person['customer_id']
                ? Subscription::query()->where('tenant_id', $tenantId)->where('customer_id', $person['customer_id'])
                    ->where('notes', 'like', 'SBUK ERP:%')->whereIn('status', ['pending', 'active'])->first()
                : null;
            if ($managedSubscription && ((int) $managedSubscription->plan_id !== $planId
                || (int) $managedSubscription->router_id !== $routerId
                || (int) $managedSubscription->ip_pool_id !== $ipPoolId)) {
                $person['error'] = 'Existing SBUK subscription configuration conflict.';
                $summary['invalid_or_conflicting']++;
                $summary['valid_records']--;
                $summary[$person['customer_id'] ? 'existing_users' : 'new_users']--;
            } elseif ($managedSubscription || $subscription) {
                $summary['already_subscribed']++;
            } else {
                $summary['new_subscriptions']++;
            }
        }
        unset($person);

        return ['records' => $normalized, 'summary' => $summary];
    }

    /** @param array<int, array<string, mixed>> $records
     * @return array<int, array{first_name: string, last_name: string, mobile: ?string, national_id: ?string, external_index: ?string, error: ?string, customer_id: ?int}>
     */
    public function normalize(array $records): array
    {
        $normalized = [];
        foreach ($records as $record) {
            $firstName = trim((string) ($record['FNAME'] ?? ''));
            $lastName = trim((string) ($record['LNAME'] ?? ''));
            $nationalId = $this->digits((string) ($record['NATIONALID'] ?? ''));
            $mobile = $this->normalizeMobile((string) ($record['MOBILE'] ?? ''));
            $error = match (true) {
                $firstName === '' || $lastName === '' => 'Name is incomplete.',
                ! preg_match('/^\d{10}$/', $nationalId) => 'National ID is missing or invalid.',
                filled($record['MOBILE'] ?? null) && ! preg_match('/^09\d{9}$/', $this->normalizeMobile((string) $record['MOBILE'])) => 'Mobile number is invalid.',
                default => null,
            };
            $index = $record['index_'] ?? null;
            $normalized[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'mobile' => $mobile !== '' && preg_match('/^09\d{9}$/', $mobile) ? $mobile : null,
                'national_id' => $nationalId !== '' ? $nationalId : null,
                'external_index' => is_scalar($index) ? mb_substr((string) $index, 0, 255) : null,
                'error' => $error,
                'customer_id' => null,
            ];
        }

        return $normalized;
    }

    public function process(SbukSyncRun $run): void
    {
        $records = json_decode((string) $run->personnel_snapshot, true, flags: JSON_THROW_ON_ERROR);
        $run->update(['status' => 'processing', 'started_at' => now()]);
        $errors = [];
        foreach ($records as $index => $person) {
            if ($person['error'] !== null) {
                $this->incrementRunCounter($run, 'failed_records');
                $outcome = 'invalid_record';
                $detail = $person['error'];
            } else {
                try {
                    $result = $this->processPerson($run, $person);
                    foreach ($result as $counter) {
                        $this->incrementRunCounter($run, $counter);
                    }
                    $outcome = in_array('created_subscriptions', $result, true) ? 'subscription_created'
                        : (in_array('skipped_subscriptions', $result, true) ? 'subscription_skipped'
                            : (in_array('created_customers', $result, true) ? 'created' : 'already_exists'));
                    $detail = in_array('created_subscriptions', $result, true)
                        ? 'Pending activation: the ERP response does not include a connection profile or service credentials.'
                        : null;
                } catch (\Throwable $exception) {
                    $this->incrementRunCounter($run, 'failed_records');
                    $isIdentityConflict = $exception instanceof ValidationException
                        && array_intersect(['national_id', 'subscription'], array_keys($exception->errors())) !== [];
                    $outcome = $isIdentityConflict ? 'identity_conflict' : 'provisioning_failed';
                    $detail = $isIdentityConflict ? 'Identity or subscription configuration requires manual review.' : 'IP allocation or subscription provisioning failed.';
                    $errors[] = $detail;
                    Log::warning('SBUK ERP personnel row failed.', ['run_id' => $run->id, 'row_number' => $index + 1, 'outcome' => $outcome]);
                }
            }
            SbukSyncRunRecord::query()->create([
                'tenant_id' => $run->tenant_id,
                'sbuk_sync_run_id' => $run->id,
                'row_number' => $index + 1,
                'identity_hash' => filled($person['national_id']) ? $this->identityHash($person['national_id']) : null,
                'outcome' => $outcome,
                'detail' => $detail,
            ]);
            $this->incrementRunCounter($run, 'processed_records');
        }
        $run = SbukSyncRun::query()->where('tenant_id', $run->tenant_id)->findOrFail($run->id);
        $run->update([
            'status' => $run->failed_records > 0 ? 'completed_with_errors' : 'completed',
            'error_summary' => $errors === [] ? null : implode(' ', array_unique($errors)),
            'completed_at' => now(),
            'personnel_snapshot' => null,
        ]);
    }

    private function processPerson(SbukSyncRun $run, array $person): array
    {
        if ($person['error'] !== null) {
            return ['failed_records'];
        }

        return DB::transaction(function () use ($run, $person): array {
            $hash = $this->identityHash($person['national_id']);
            $identity = SbukPersonnelIdentity::query()->where('tenant_id', $run->tenant_id)
                ->where('identity_hash', $hash)->lockForUpdate()->first();
            $matches = $this->customersForIdentity($run->tenant_id, $person['national_id']);
            if ($matches->count() > 1) {
                throw ValidationException::withMessages(['national_id' => 'Conflicting customer identity.']);
            }
            if ($identity && $matches->isNotEmpty() && (int) $identity->customer_id !== (int) $matches->first()->id) {
                throw ValidationException::withMessages(['national_id' => 'Conflicting customer identity mapping.']);
            }
            $customer = $identity?->customer ?? $matches->first();
            $created = $customer === null;
            if ($created) {
                $customer = Customer::query()->create([
                    'tenant_id' => $run->tenant_id,
                    'customer_type' => 'individual',
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'name' => trim($person['first_name'].' '.$person['last_name']),
                    'national_id' => $person['national_id'],
                    'mobile' => $person['mobile'],
                    'phone' => $person['mobile'],
                    'status' => 'active',
                    'billing_enabled' => false,
                ]);
            } else {
                $customer->update([
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'name' => trim($person['first_name'].' '.$person['last_name']),
                    'mobile' => $person['mobile'] ?? $customer->mobile,
                    'phone' => $person['mobile'] ?? $customer->phone,
                ]);
            }
            if (! $identity) {
                SbukPersonnelIdentity::query()->create([
                    'tenant_id' => $run->tenant_id,
                    'customer_id' => $customer->id,
                    'identity_hash' => $hash,
                    'external_index' => $person['external_index'],
                ]);
            }

            $managedSubscription = Subscription::query()->where('tenant_id', $run->tenant_id)
                ->where('customer_id', $customer->id)->where('notes', 'like', 'SBUK ERP:%')
                ->whereIn('status', ['pending', 'active'])->lockForUpdate()->first();
            if ($managedSubscription) {
                if ((int) $managedSubscription->plan_id !== (int) $run->plan_id
                    || (int) $managedSubscription->router_id !== (int) $run->router_id
                    || (int) $managedSubscription->ip_pool_id !== (int) $run->ip_pool_id) {
                    throw ValidationException::withMessages(['subscription' => 'Existing SBUK subscription configuration conflict.']);
                }

                return [$created ? 'created_customers' : 'matched_customers', 'skipped_subscriptions'];
            }
            $existing = Subscription::query()->where('tenant_id', $run->tenant_id)
                ->where('customer_id', $customer->id)->where('plan_id', $run->plan_id)
                ->whereIn('status', ['pending', 'active'])->lockForUpdate()->first();
            if ($existing) {
                return [$created ? 'created_customers' : 'matched_customers', 'skipped_subscriptions'];
            }
            $plan = Plan::query()->where('tenant_id', $run->tenant_id)->active()->findOrFail($run->plan_id);
            $router = Router::query()->where('tenant_id', $run->tenant_id)->findOrFail($run->router_id);
            $pool = IpPool::query()->where('tenant_id', $run->tenant_id)->active()->findOrFail($run->ip_pool_id);
            if ($router->status !== 'online' || ! $this->poolSupportsRouter($pool, $router->id, $run->tenant_id)) {
                throw ValidationException::withMessages(['router_id' => 'The selected router or pool is no longer usable.']);
            }
            $mapping = $run->preview_summary['pppoe_mapping'] ?? [];
            $subscription = Subscription::query()->create([
                'tenant_id' => $run->tenant_id,
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->mobile,
                'pppoe_username' => $person[$mapping['username'] ?? 'mobile'],
                'pppoe_password' => $person[$mapping['password'] ?? 'national_id'],
                'plan_id' => $plan->id,
                'router_id' => $run->router_id,
                'ip_pool_id' => $pool->id,
                'ip_management' => 'system',
                'ip_allocation' => 'fixed',
                'service_type' => 'hotspot',
                'billing_cycle' => $plan->billing_cycle,
                'billing_enabled' => false,
                'auto_suspension_enabled' => false,
                'billing_disabled_at' => now(),
                'base_price' => 0,
                'total_price' => 0,
                'status' => 'pending',
                'notes' => 'SBUK ERP: run '.$run->id,
            ]);
            if (! $subscription->assignIpAddress()) {
                throw ValidationException::withMessages(['ip_pool_id' => 'No available IP address could be allocated.']);
            }

            return [$created ? 'created_customers' : 'matched_customers', 'created_subscriptions'];
        }, 3);
    }

    private function customersForIdentity(string $tenantId, string $nationalId): Collection
    {
        return Customer::query()->where('tenant_id', $tenantId)->where('national_id', $nationalId)->limit(2)->get();
    }

    private function poolSupportsRouter(IpPool $pool, int $routerId, string $tenantId): bool
    {
        return (int) $pool->router_id === $routerId
            || $pool->all_devices
            || $pool->routers()->where('routers.tenant_id', $tenantId)->whereKey($routerId)->exists();
    }

    private function identityHash(string $nationalId): string
    {
        return hash_hmac('sha256', $nationalId, (string) config('app.key'));
    }

    private function incrementRunCounter(SbukSyncRun $run, string $counter): void
    {
        if (! in_array($counter, [
            'failed_records', 'created_customers', 'matched_customers', 'created_subscriptions',
            'skipped_subscriptions', 'processed_records',
        ], true)) {
            throw new \InvalidArgumentException('Unsupported SBUK synchronization counter.');
        }

        SbukSyncRun::query()->where('tenant_id', $run->tenant_id)->whereKey($run->id)->increment($counter);
        $run->setAttribute($counter, (int) $run->getAttribute($counter) + 1);
    }

    private function normalizeMobile(string $mobile): string
    {
        $digits = $this->digits($mobile);
        if (str_starts_with($digits, '0098')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D/u', '', strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ])) ?? '';
    }
}
