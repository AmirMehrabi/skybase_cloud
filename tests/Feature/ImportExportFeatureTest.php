<?php

namespace Tests\Feature;

use App\Jobs\ImportExport\ProcessExportJob;
use App\Jobs\ImportExport\ProcessImportJob;
use App\Models\Customer;
use App\Models\ImportExportRun;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ImportExport\ImportExportSchema;
use App\Support\ImportExport\SpreadsheetImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_export_request_queues_a_run_with_current_filters(): void
    {
        Queue::fake();
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->post(route('plans.export'), [
            'status' => 'active',
            'type' => 'pppoe',
        ]);

        $response->assertRedirect(route('plans.index'));
        $this->assertDatabaseHas('import_export_runs', [
            'tenant_id' => $tenant->id,
            'module' => 'plans',
            'direction' => 'export',
            'status' => 'queued',
        ]);

        $run = ImportExportRun::query()->firstOrFail();
        $this->assertSame(['status' => 'active', 'type' => 'pppoe'], $run->filters);
        Queue::assertPushed(ProcessExportJob::class);
    }

    public function test_plan_import_request_stores_the_uploaded_file_before_queueing(): void
    {
        Queue::fake();
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->post(route('plans.import'), [
            'file' => UploadedFile::fake()->createWithContent('plans.xlsx', 'placeholder'),
        ]);

        $response->assertRedirect(route('plans.index'));
        $run = ImportExportRun::query()->firstOrFail();

        $this->assertNotNull($run->file_path);
        Storage::disk('imports')->assertExists($run->file_path);
        Queue::assertPushed(ProcessImportJob::class);
    }

    public function test_plan_export_job_writes_filtered_xlsx_file(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);

        Plan::factory()->create([
            'name' => 'Exported Fiber',
            'internal_name' => 'exported_fiber',
            'status' => 'active',
            'type' => 'pppoe',
        ]);
        Plan::factory()->create([
            'name' => 'Hidden Wireless',
            'internal_name' => 'hidden_wireless',
            'status' => 'inactive',
            'type' => 'wireless',
        ]);

        $run = ImportExportRun::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => ImportExportSchema::MODULE_PLANS,
            'direction' => ImportExportRun::DIRECTION_EXPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'filters' => ['status' => 'active'],
            'disk' => 'imports',
        ]);

        (new ProcessExportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->total_rows);
        Storage::disk('imports')->assertExists($run->file_path);

        $sheet = IOFactory::load(Storage::disk('imports')->path($run->file_path))->getActiveSheet();
        $this->assertSame('internal_name', $sheet->getCell('B1')->getValue());
        $this->assertSame('exported_fiber', $sheet->getCell('B2')->getValue());
        $this->assertNull($sheet->getCell('B3')->getValue());
    }

    public function test_subscription_export_job_writes_plan_name_column(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'internal_name' => 'fiber_100',
            'status' => 'active',
        ]);
        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-EXP-0001',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'subscription_code' => 'SUB-EXP-0001',
            'name' => 'Jane Home Fiber',
            'service_type' => 'pppoe',
            'connection_type' => 'pppoe',
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'billing_enabled' => true,
            'pppoe_username' => 'jane.pppoe',
            'pppoe_password' => 'secret',
            'base_price' => $plan->price,
            'total_price' => $plan->price,
        ]);

        $run = ImportExportRun::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => ImportExportSchema::MODULE_SUBSCRIPTIONS,
            'direction' => ImportExportRun::DIRECTION_EXPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'disk' => 'imports',
        ]);

        (new ProcessExportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        Storage::disk('imports')->assertExists($run->file_path);

        $sheet = IOFactory::load(Storage::disk('imports')->path($run->file_path))->getActiveSheet();
        $this->assertSame('plan_name', $sheet->getCell('AA1')->getValue());
        $this->assertSame('Fiber 100', $sheet->getCell('AA2')->getValue());
        $this->assertSame('CUS-EXP-0001', $sheet->getCell('A2')->getValue());
        $this->assertSame('SUB-EXP-0001', $sheet->getCell('X2')->getValue());
    }

    public function test_plan_import_upserts_rows_and_reports_validation_failures(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        Plan::factory()->create([
            'name' => 'Old Name',
            'internal_name' => 'existing_plan',
            'status' => 'active',
            'type' => 'pppoe',
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_PLANS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_PLANS), [
            ['Updated Name', 'existing_plan', null, 'active', 'public', 'pppoe', 'Residential', 100, 50, 0, 0, 'Mbps', null, 'GB', 'yes', 49.99, 'USD', 'monthly', 7, 0, null, null, null, 5, 'no', null, null, null, null],
            ['Bad Plan', 'bad_plan', null, 'invalid', 'public', 'pppoe', 'Residential', 100, 50, 0, 0, 'Mbps', null, 'GB', 'yes', 49.99, 'USD', 'monthly', 7, 0, null, null, null, 5, 'no', null, null, null, null],
        ]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(2, $run->processed_rows);
        $this->assertSame(1, $run->updated_count);
        $this->assertSame(1, $run->failed_count);
        $this->assertDatabaseHas('plans', [
            'internal_name' => 'existing_plan',
            'name' => 'Updated Name',
        ]);
        $this->assertDatabaseHas('import_export_run_rows', [
            'import_export_run_id' => $run->id,
            'row_number' => 3,
            'status' => 'failed',
            'identifier' => 'bad_plan',
        ]);
    }

    public function test_plan_import_generates_internal_name_from_name_when_missing(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_PLANS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_PLANS), [[
            'Super Fiber 200',
            null,
            null,
            'active',
            'public',
            'pppoe',
            'Residential',
            200,
            100,
            0,
            0,
            'Mbps',
            null,
            'GB',
            'yes',
            89.99,
            'USD',
            'monthly',
            7,
            0,
            null,
            null,
            null,
            5,
            'no',
            null,
            null,
            null,
            null,
        ]]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->created_count);
        $this->assertDatabaseHas('plans', [
            'name' => 'Super Fiber 200',
            'internal_name' => 'super-fiber-200',
        ]);
    }

    public function test_subscription_import_creates_customer_and_subscription_from_one_row(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'internal_name' => 'fiber_100',
            'status' => 'active',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_SUBSCRIPTIONS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS), [[
            'customer_code' => 'CUS-IMP-0001',
            'customer_type' => 'individual',
            'customer_name' => 'Jane Doe',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'company_name' => null,
            'national_id' => 'NAT-001',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
            'mobile' => '555-0101',
            'whatsapp' => null,
            'address_line1' => '123 Main',
            'address_line2' => null,
            'city' => 'Tehran',
            'state' => 'Tehran',
            'postal_code' => '12345',
            'country' => 'Iran',
            'customer_status' => 'active',
            'billing_type' => 'prepaid',
            'customer_billing_enabled' => 'yes',
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => 'no',
            'subscription_code' => 'SUB-IMP-0001',
            'subscription_name' => 'Jane Home Fiber',
            'service_type' => 'pppoe',
            'plan_name' => $plan->name,
            'router_name' => $router->name,
            'site' => 'North POP',
            'connection_type' => 'pppoe',
            'ip_address' => '10.0.0.10',
            'mac_address' => null,
            'ip_management' => 'router',
            'pppoe_username' => 'jane.pppoe',
            'pppoe_password' => 'secret',
            'base_price' => 79.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 79.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => 'yes',
            'grace_period_days' => 7,
            'next_billing_date' => '2026-06-10',
            'status' => 'active',
            'start_date' => '2026-06-01 00:00:00',
            'end_date' => null,
            'activation_date' => '2026-06-01 00:00:00',
            'suspended_at' => null,
            'cancelled_at' => null,
            'notes' => 'Imported service',
        ]]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->created_count);
        $this->assertSame(0, $run->failed_count);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-IMP-0001',
            'email' => 'jane@example.com',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_code' => 'SUB-IMP-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'pppoe_username' => 'jane.pppoe',
        ]);
    }

    public function test_subscription_import_reuses_customers_and_uses_plan_fallbacks(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create([
            'name' => 'Fiber 250',
            'internal_name' => 'fiber_250',
            'status' => 'active',
            'price' => 129.99,
            'billing_cycle' => 'quarterly',
            'grace_period_days' => 12,
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_SUBSCRIPTIONS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $headings = ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS);

        $this->writeWorkbook($path, $headings, [
            [
                'customer_code' => null,
                'customer_type' => 'individual',
                'customer_name' => null,
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'company_name' => null,
                'national_id' => null,
                'email' => 'jane@example.com',
                'phone' => null,
                'mobile' => null,
                'whatsapp' => null,
                'address_line1' => null,
                'address_line2' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'customer_status' => 'active',
                'billing_type' => 'prepaid',
                'customer_billing_enabled' => 'yes',
                'balance' => null,
                'credit_limit' => null,
                'tax_exempt' => null,
                'subscription_code' => null,
                'subscription_name' => 'Jane Home Fiber',
                'service_type' => 'pppoe',
                'plan_name' => $plan->name,
                'router_name' => null,
                'site' => 'North POP',
                'connection_type' => 'pppoe',
                'ip_address' => '10.0.0.10',
                'mac_address' => null,
                'ip_management' => 'router',
                'pppoe_username' => 'jane.home.1',
                'pppoe_password' => 'secret',
                'base_price' => null,
                'discount_amount' => null,
                'discount_type' => null,
                'tax_amount' => null,
                'total_price' => null,
                'billing_cycle' => null,
                'billing_enabled' => null,
                'grace_period_days' => null,
                'next_billing_date' => null,
                'status' => 'active',
                'start_date' => null,
                'end_date' => null,
                'activation_date' => null,
                'suspended_at' => null,
                'cancelled_at' => null,
                'notes' => 'First import row',
            ],
            [
                'customer_code' => null,
                'customer_type' => 'individual',
                'customer_name' => null,
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'company_name' => null,
                'national_id' => null,
                'email' => 'jane@example.com',
                'phone' => null,
                'mobile' => null,
                'whatsapp' => null,
                'address_line1' => null,
                'address_line2' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'customer_status' => 'active',
                'billing_type' => 'prepaid',
                'customer_billing_enabled' => 'yes',
                'balance' => null,
                'credit_limit' => null,
                'tax_exempt' => null,
                'subscription_code' => null,
                'subscription_name' => 'Jane Home Fiber 2',
                'service_type' => 'pppoe',
                'plan_name' => $plan->name,
                'router_name' => null,
                'site' => 'North POP',
                'connection_type' => 'pppoe',
                'ip_address' => '10.0.0.11',
                'mac_address' => null,
                'ip_management' => 'router',
                'pppoe_username' => 'jane.home.2',
                'pppoe_password' => 'secret',
                'base_price' => null,
                'discount_amount' => null,
                'discount_type' => null,
                'tax_amount' => null,
                'total_price' => null,
                'billing_cycle' => null,
                'billing_enabled' => null,
                'grace_period_days' => null,
                'next_billing_date' => null,
                'status' => 'active',
                'start_date' => null,
                'end_date' => null,
                'activation_date' => null,
                'suspended_at' => null,
                'cancelled_at' => null,
                'notes' => 'Second import row',
            ],
        ]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(2, $run->created_count);

        $customer = Customer::query()->where('tenant_id', $tenant->id)->where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('Jane Doe', $customer->name);
        $this->assertNotNull($customer->customer_code);
        $this->assertSame(2, $customer->subscriptions()->count());

        $firstSubscription = $customer->subscriptions()->where('pppoe_username', 'jane.home.1')->firstOrFail();
        $secondSubscription = $customer->subscriptions()->where('pppoe_username', 'jane.home.2')->firstOrFail();

        $this->assertSame($plan->price, $firstSubscription->base_price);
        $this->assertSame($plan->billing_cycle, $firstSubscription->billing_cycle);
        $this->assertSame($plan->grace_period_days, $firstSubscription->grace_period_days);
        $this->assertSame($plan->price, $secondSubscription->base_price);
        $this->assertSame($plan->billing_cycle, $secondSubscription->billing_cycle);
        $this->assertSame($plan->grace_period_days, $secondSubscription->grace_period_days);
        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.home.1',
            'attribute' => 'Cleartext-Password',
        ]);
        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.home.2',
            'attribute' => 'Cleartext-Password',
        ]);
    }

    public function test_subscription_import_links_company_name_to_an_organization(): void
    {
        Storage::fake('imports');

        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'internal_name' => 'fiber_100',
            'status' => 'active',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_SUBSCRIPTIONS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';

        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS), [[
            'customer_code' => 'CUS-ORG-0001',
            'customer_type' => 'business',
            'customer_name' => 'Acme ISP',
            'first_name' => null,
            'last_name' => null,
            'company_name' => 'Acme ISP',
            'national_id' => null,
            'email' => 'billing@acme.test',
            'phone' => null,
            'mobile' => null,
            'whatsapp' => null,
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'customer_status' => 'active',
            'billing_type' => 'prepaid',
            'customer_billing_enabled' => 'yes',
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => 'no',
            'subscription_code' => 'SUB-ORG-0001',
            'subscription_name' => 'Acme Business Fiber',
            'service_type' => 'pppoe',
            'plan_name' => $plan->name,
            'router_name' => null,
            'site' => null,
            'connection_type' => 'pppoe',
            'ip_address' => '10.50.0.10',
            'mac_address' => null,
            'ip_management' => 'router',
            'pppoe_username' => 'acme.pppoe',
            'pppoe_password' => 'secret',
            'base_price' => 99.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 99.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => 'yes',
            'grace_period_days' => 7,
            'next_billing_date' => '2026-06-10',
            'status' => 'active',
            'start_date' => '2026-06-01 00:00:00',
            'end_date' => null,
            'activation_date' => '2026-06-01 00:00:00',
            'suspended_at' => null,
            'cancelled_at' => null,
            'notes' => 'Imported business service',
        ]]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);

        $organization = Organization::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Acme ISP')
            ->firstOrFail();

        $customer = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_code', 'CUS-ORG-0001')
            ->firstOrFail();

        $subscription = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('subscription_code', 'SUB-ORG-0001')
            ->firstOrFail();

        $this->assertSame($organization->id, $customer->organization_id);
        $this->assertSame($customer->id, $subscription->customer_id);
        $this->assertSame('business', $customer->customer_type);
        $this->assertSame('Acme ISP', $customer->company_name);
    }

    public function test_subscription_import_splits_multiple_ips_into_primary_and_ip_routes(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $planName = 'Fiber 300 Import '.substr((string) Str::uuid(), 0, 8);
        $routerName = 'Core Router '.substr((string) Str::uuid(), 0, 8);
        $subscriptionCode = 'SUB-IMP-'.substr((string) Str::uuid(), 0, 8);
        $username = 'john.routed.'.substr((string) Str::uuid(), 0, 8);
        $plan = Plan::factory()->create([
            'name' => $planName,
            'internal_name' => 'fiber_300_'.substr((string) Str::uuid(), 0, 8),
            'status' => 'active',
            'price' => 149.99,
            'billing_cycle' => 'monthly',
        ]);
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => $routerName,
            'vendor' => 'Cisco',
            'enable_provisioning' => false,
        ]);
        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'name' => 'Import Pool',
            'network_address' => '172.16.111.0',
            'cidr' => 24,
            'gateway' => '172.16.111.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'site' => 'North POP',
            'total_ips' => 254,
            'used_ips' => 0,
            'reserved_ips' => 0,
            'available_ips' => 254,
        ]);
        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '172.16.111.76',
            'status' => 'available',
        ]);
        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '172.16.111.77',
            'status' => 'available',
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_SUBSCRIPTIONS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS), [[
            'customer_code' => 'CUS-IMP-0002',
            'customer_type' => 'individual',
            'customer_name' => 'John Smith',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'company_name' => null,
            'national_id' => 'NAT-002',
            'email' => 'john@example.com',
            'phone' => '555-0200',
            'mobile' => '555-0201',
            'whatsapp' => null,
            'address_line1' => '456 Main',
            'address_line2' => null,
            'city' => 'Tehran',
            'state' => 'Tehran',
            'postal_code' => '12345',
            'country' => 'Iran',
            'customer_status' => 'active',
            'billing_type' => 'prepaid',
            'customer_billing_enabled' => 'yes',
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => 'no',
            'subscription_code' => $subscriptionCode,
            'subscription_name' => 'John Routed Fiber',
            'service_type' => 'pppoe',
            'plan_name' => $plan->name,
            'router_name' => $router->name,
            'site' => 'North POP',
            'connection_type' => 'pppoe',
            'ip_address' => '172.16.111.76/30, 172.16.111.77/30',
            'mac_address' => null,
            'ip_management' => 'router',
            'pppoe_username' => $username,
            'pppoe_password' => 'secret',
            'base_price' => 149.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 149.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => 'yes',
            'grace_period_days' => 7,
            'next_billing_date' => '2026-06-10',
            'status' => 'active',
            'start_date' => '2026-06-01 00:00:00',
            'end_date' => null,
            'activation_date' => '2026-06-01 00:00:00',
            'suspended_at' => null,
            'cancelled_at' => null,
            'notes' => 'Imported service with routes',
        ]]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(1, $run->created_count);

        $subscription = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('subscription_code', $subscriptionCode)
            ->firstOrFail();

        $this->assertSame('system', $subscription->ip_management);
        $this->assertSame($pool->id, $subscription->ip_pool_id);
        $this->assertSame('172.16.111.76', $subscription->ip_address);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '172.16.111.76',
            'status' => 'assigned',
            'customer_id' => $subscription->customer_id,
            'subscription_code' => $subscriptionCode,
        ]);

        $this->assertDatabaseHas('subscription_ip_routes', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '172.16.111.77',
            'cidr' => 30,
        ]);

        $route = SubscriptionIpRoute::query()
            ->where('tenant_id', $tenant->id)
            ->where('subscription_id', $subscription->id)
            ->where('ip_address', '172.16.111.77')
            ->firstOrFail();

        $this->assertNotNull($route->ip_address_id);
        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '172.16.111.77',
            'status' => 'assigned',
            'customer_id' => $subscription->customer_id,
            'subscription_code' => null,
        ]);
    }

    public function test_subscription_import_with_suspended_customer_rejects_radius_access(): void
    {
        Storage::fake('imports');
        $tenant = $this->createTenant('alpha-net');
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create([
            'name' => 'Fiber 50',
            'internal_name' => 'fiber_50',
            'status' => 'active',
            'price' => 39.99,
            'billing_cycle' => 'monthly',
            'grace_period_days' => 7,
        ]);

        $run = $this->createImportRun($tenant, $user, ImportExportSchema::MODULE_SUBSCRIPTIONS);
        $path = ImportExportSchema::basePath($tenant->id, $run->id).'/import-'.$run->id.'.xlsx';
        $this->writeWorkbook($path, ImportExportSchema::headings(ImportExportSchema::MODULE_SUBSCRIPTIONS), [[
            'customer_code' => null,
            'customer_type' => 'individual',
            'customer_name' => null,
            'first_name' => 'Ali',
            'last_name' => 'Rezaei',
            'company_name' => null,
            'national_id' => null,
            'email' => 'ali@example.com',
            'phone' => null,
            'mobile' => null,
            'whatsapp' => null,
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'customer_status' => 'suspended',
            'billing_type' => 'prepaid',
            'customer_billing_enabled' => 'yes',
            'balance' => null,
            'credit_limit' => null,
            'tax_exempt' => null,
            'subscription_code' => null,
            'subscription_name' => 'Ali Home Fiber',
            'service_type' => 'pppoe',
            'plan_name' => $plan->name,
            'router_name' => null,
            'site' => 'North POP',
            'connection_type' => 'pppoe',
            'ip_address' => '10.0.0.20',
            'mac_address' => null,
            'ip_management' => 'router',
            'pppoe_username' => 'ali.pppoe',
            'pppoe_password' => 'secret',
            'base_price' => null,
            'discount_amount' => null,
            'discount_type' => null,
            'tax_amount' => null,
            'total_price' => null,
            'billing_cycle' => null,
            'billing_enabled' => null,
            'grace_period_days' => null,
            'next_billing_date' => null,
            'status' => 'active',
            'start_date' => null,
            'end_date' => null,
            'activation_date' => null,
            'suspended_at' => null,
            'cancelled_at' => null,
            'notes' => 'Suspended import row',
        ]]);
        $run->update(['file_path' => $path]);

        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();

        $this->assertSame(ImportExportRun::STATUS_COMPLETED, $run->status);
        $customer = Customer::query()->where('tenant_id', $tenant->id)->where('email', 'ali@example.com')->firstOrFail();
        $this->assertSame('suspended', $customer->status);
        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'ali.pppoe',
            'attribute' => 'Auth-Type',
            'value' => 'Reject',
        ]);
        $this->assertDatabaseMissing('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'ali.pppoe',
            'attribute' => 'Cleartext-Password',
        ]);
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function createUser(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createImportRun(Tenant $tenant, User $user, string $module): ImportExportRun
    {
        return ImportExportRun::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'module' => $module,
            'direction' => ImportExportRun::DIRECTION_IMPORT,
            'status' => ImportExportRun::STATUS_QUEUED,
            'disk' => 'imports',
            'original_filename' => 'import.xlsx',
        ]);
    }

    /**
     * @param  list<string>  $headings
     * @param  list<array<int, mixed>|array<string, mixed>>  $rows
     */
    private function writeWorkbook(string $path, array $headings, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headings as $index => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $heading);
        }

        foreach ($rows as $rowIndex => $row) {
            $orderedRow = array_is_list($row)
                ? $row
                : array_map(fn (string $heading): mixed => $row[$heading] ?? null, $headings);

            foreach ($orderedRow as $columnIndex => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 2), $value);
            }
        }

        Storage::disk('imports')->makeDirectory(dirname($path));
        (new Xlsx($spreadsheet))->save(Storage::disk('imports')->path($path));
        $spreadsheet->disconnectWorksheets();
    }
}
