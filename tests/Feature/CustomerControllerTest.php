<?php

namespace Tests\Feature;

use App\Jobs\BulkDeleteModelsJob;
use App\Models\BulkDeletionRun;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\Invoice;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketTeam;
use App\Models\User;
use App\Services\BulkDeletionService;
use App\Services\RouterOs\RouterOsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_redirects_browser_requests_to_the_customers_index_page(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('customers.store'), $this->validPayload());

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'email' => 'jane.doe@example.com',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_show_page_renders_real_customer_subscriptions_invoices_and_payments(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-REAL-0001',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane.real@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Fiber 300',
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-REAL-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Downtown',
            'connection_type' => 'pppoe',
            'pppoe_username' => 'jane.real',
            'pppoe_password' => 'secret',
            'base_price' => 100,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 100,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
            'activation_date' => now(),
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-REAL-0001',
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'issue_date' => today(),
            'due_date' => today()->addDays(7),
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'paid_amount' => 25,
            'balance_due' => 75,
            'status' => 'partially_paid',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-REAL-0001',
            'amount' => 25,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('SUB-REAL-0001');
        $response->assertSee('Subscriptions');
        $response->assertSee('Subscription Access');
        $response->assertSee('jane.real');
        $response->assertSee('Show');
        $response->assertSee('Fiber 300');
        $response->assertSee('INV-REAL-0001');
        $response->assertSee('PAY-REAL-0001');
        $response->assertDontSee('Services');
        $response->assertDontSee('INV-2024-0156');
        $response->assertDontSee('Mikrotik-01');
    }

    public function test_show_page_renders_customer_tickets_and_notes(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::withoutGlobalScopes()->create(array_merge($this->validPayload(), [
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TICKET-0001',
            'name' => 'Jane Doe',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
        ]));

        $team = TicketTeam::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Network Operations',
            'slug' => 'network-operations',
            'status' => 'active',
            'assignment_strategy' => TicketTeam::STRATEGY_QUEUE,
            'first_response_minutes' => 240,
            'resolution_minutes' => 2880,
        ]);

        Ticket::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'ticket_number' => 'TCK-CUSTOMER-0001',
            'customer_id' => $customer->id,
            'ticket_team_id' => $team->id,
            'opened_by_user_id' => $user->id,
            'source' => 'admin_portal',
            'subject' => 'Customer router follow up',
            'priority' => Ticket::PRIORITY_HIGH,
            'status' => Ticket::STATUS_OPEN,
            'last_activity_at' => now(),
        ]);

        CustomerNote::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'body' => 'Prefers morning maintenance windows.',
        ]);

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('TCK-CUSTOMER-0001');
        $response->assertSee('Customer router follow up');
        $response->assertSee('Network Operations');
        $response->assertSee('customer_id='.$customer->id);
        $response->assertSee('Prefers morning maintenance windows.');
        $response->assertSee('Add Customer Note');
    }

    public function test_customers_data_uses_all_subscription_plans_routers_and_ips(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-LIST-0001',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane.list@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $firstPlan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $secondPlan = Plan::factory()->create([
            'name' => 'Fiber 300',
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $firstRouter = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        $secondRouter = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Edge Router',
        ]);

        Subscription::withoutEvents(function () use ($customer, $tenant, $firstPlan, $secondPlan, $firstRouter, $secondRouter): void {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'subscription_code' => 'SUB-LIST-0001',
                'name' => 'First Service',
                'service_type' => 'pppoe',
                'plan_id' => $firstPlan->id,
                'router_id' => $firstRouter->id,
                'site' => 'Downtown',
                'ip_address' => '10.10.0.10',
                'connection_type' => 'pppoe',
                'pppoe_username' => 'jane.one',
                'pppoe_password' => 'secret-one',
                'base_price' => 50,
                'discount_amount' => 0,
                'discount_type' => 'none',
                'tax_amount' => 0,
                'total_price' => 50,
                'billing_cycle' => 'monthly',
                'billing_enabled' => true,
                'status' => 'active',
                'start_date' => now()->subMonth(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ]);

            Subscription::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'subscription_code' => 'SUB-LIST-0002',
                'name' => 'Second Service',
                'service_type' => 'pppoe',
                'plan_id' => $secondPlan->id,
                'router_id' => $secondRouter->id,
                'site' => 'Warehouse',
                'ip_address' => '10.10.0.20',
                'connection_type' => 'pppoe',
                'pppoe_username' => 'jane.two',
                'pppoe_password' => 'secret-two',
                'base_price' => 75,
                'discount_amount' => 0,
                'discount_type' => 'none',
                'tax_amount' => 0,
                'total_price' => 75,
                'billing_cycle' => 'monthly',
                'billing_enabled' => true,
                'status' => 'active',
                'start_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $response = $this->actingAs($user)->getJson(route('customers.data'));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['Fiber 100', 'Fiber 300'], $response->json('customers.0.plans'));
        $this->assertEqualsCanonicalizing(['Warehouse / Edge Router', 'Downtown / Core Router'], $response->json('customers.0.site_router'));
        $this->assertEqualsCanonicalizing(['10.10.0.20', '10.10.0.10'], $response->json('customers.0.ip_addresses'));
        $this->assertContains($response->json('customers.0.plan'), ['Fiber 100', 'Fiber 300']);
        $this->assertContains($response->json('customers.0.site'), ['Warehouse / Edge Router', 'Downtown / Core Router']);
        $this->assertContains($response->json('customers.0.router'), ['Core Router', 'Edge Router']);
        $this->assertContains($response->json('customers.0.ip_address'), ['10.10.0.10', '10.10.0.20']);
    }

    public function test_customers_data_search_matches_partial_pppoe_username_from_subscriptions(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $matchingCustomer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-LIST-0100',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane.search@example.com',
            'mobile' => '555-0110',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $otherCustomer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-LIST-0101',
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'name' => 'John Smith',
            'email' => 'john.search@example.com',
            'mobile' => '555-0111',
            'address_line1' => '456 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $matchingCustomer->id,
            'subscription_code' => 'SUB-LIST-0100',
            'name' => 'Matching Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Downtown',
            'ip_address' => '10.10.0.30',
            'connection_type' => 'pppoe',
            'pppoe_username' => 'home.alpha.user',
            'pppoe_password' => 'secret-one',
            'base_price' => 50,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 50,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $otherCustomer->id,
            'subscription_code' => 'SUB-LIST-0101',
            'name' => 'Non Matching Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Warehouse',
            'ip_address' => '10.10.0.40',
            'connection_type' => 'pppoe',
            'pppoe_username' => 'other.user',
            'pppoe_password' => 'secret-two',
            'base_price' => 75,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 75,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('customers.data', ['search' => 'alpha.us']));

        $response->assertOk();
        $this->assertSame(1, $response->json('pagination.total'));
        $response->assertJsonPath('customers.0.id', $matchingCustomer->id);
        $response->assertJsonPath('customers.0.ip_address', '10.10.0.30');
    }

    public function test_customer_note_can_be_added_from_profile(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $customer = Customer::withoutGlobalScopes()->create(array_merge($this->validPayload(), [
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-NOTE-0001',
            'name' => 'Jane Doe',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
        ]));

        $response = $this->actingAs($user)->post(route('customers.notes.store', $customer), [
            'body' => 'Customer asked to call before any planned outage.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Customer note added.');

        $this->assertDatabaseHas('customer_notes', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'body' => 'Customer asked to call before any planned outage.',
        ]);
    }

    public function test_customer_note_cannot_be_added_across_tenants(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $otherTenant = $this->createTenant('beta-net', 'BetaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $customer = Customer::withoutGlobalScopes()->create(array_merge($this->validPayload(), [
            'tenant_id' => $otherTenant->id,
            'customer_code' => 'CUS-OTHER-0001',
            'email' => 'other@example.com',
            'mobile' => '555-0202',
            'name' => 'Other Customer',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
        ]));

        $this->actingAs($user)->post(route('customers.notes.store', $customer), [
            'body' => 'This should not be stored.',
        ])->assertNotFound();

        $this->assertDatabaseMissing('customer_notes', [
            'customer_id' => $customer->id,
            'body' => 'This should not be stored.',
        ]);
    }

    public function test_bulk_delete_all_filtered_customers_queues_and_deletes_customers_and_their_subscriptions(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->bindFakeRouterOsClient();

        $plan = Plan::factory()->create([
            'name' => 'Fiber 500',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 149.99,
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'name' => 'Core Pool',
            'network_address' => '10.20.0.0',
            'cidr' => 24,
            'gateway' => '10.20.0.1',
            'type' => 'static',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'site' => 'Head Office',
            'total_ips' => 4,
            'used_ips' => 0,
            'reserved_ips' => 0,
            'available_ips' => 0,
        ]);

        $firstPayload = $this->createCustomerWithSubscription(
            tenant: $tenant,
            plan: $plan,
            router: $router,
            pool: $pool,
            customerCode: 'CUS-BULK-0001',
            customerName: 'Bulk One',
            customerEmail: 'bulk.one@example.com',
            subscriptionCode: 'SUB-BULK-0001',
            invoiceNumber: 'INV-BULK-1001',
            ipAddress: '10.20.0.11',
        );

        $secondPayload = $this->createCustomerWithSubscription(
            tenant: $tenant,
            plan: $plan,
            router: $router,
            pool: $pool,
            customerCode: 'CUS-BULK-0002',
            customerName: 'Bulk Two',
            customerEmail: 'bulk.two@example.com',
            subscriptionCode: 'SUB-BULK-0002',
            invoiceNumber: 'INV-BULK-1002',
            ipAddress: '10.20.0.12',
        );

        Queue::fake();

        $response = $this->actingAs($user)->postJson(route('customers.bulk-destroy'), [
            'selection_mode' => 'all',
            'status' => 'active',
        ]);

        $response->assertAccepted();
        $response->assertJson([
            'message' => 'Customer bulk delete queued. The cleanup will run in the background.',
        ]);

        Queue::assertPushed(BulkDeleteModelsJob::class);

        $run = BulkDeletionRun::withoutGlobalScopes()->firstOrFail();
        (new BulkDeleteModelsJob($run->id))->handle(app(BulkDeletionService::class));

        foreach ([$firstPayload, $secondPayload] as $payload) {
            $this->assertSoftDeleted('customers', [
                'id' => $payload['customer']->id,
                'tenant_id' => $tenant->id,
            ]);

            $this->assertSoftDeleted('subscriptions', [
                'id' => $payload['subscription']->id,
                'tenant_id' => $tenant->id,
            ]);

            $this->assertSoftDeleted('invoices', [
                'id' => $payload['invoice']->id,
                'tenant_id' => $tenant->id,
            ]);

            $this->assertSoftDeleted('payments', [
                'id' => $payload['payment']->id,
                'tenant_id' => $tenant->id,
            ]);
        }

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.20.0.11',
            'status' => 'available',
            'subscription_code' => null,
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_address' => '10.20.0.12',
            'status' => 'available',
            'subscription_code' => null,
        ]);

        $this->assertDatabaseHas('bulk_deletion_runs', [
            'id' => $run->id,
            'tenant_id' => $tenant->id,
            'module' => BulkDeletionRun::MODULE_CUSTOMERS,
            'selection_mode' => BulkDeletionRun::SELECTION_ALL,
            'status' => BulkDeletionRun::STATUS_COMPLETED,
            'processed_count' => 2,
            'deleted_count' => 2,
            'failed_count' => 0,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'customer.bulk_delete_queued',
            'model_type' => BulkDeletionRun::class,
            'model_id' => $run->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'bulk_delete.completed',
            'model_type' => BulkDeletionRun::class,
            'model_id' => $run->id,
        ]);
    }

    private function createTenant(string $slug, string $companyName): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $companyName,
            'slug' => $slug,
            'company_name' => $companyName,
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'billing_type' => 'postpaid',
            'tax_exempt' => false,
        ];
    }

    /**
     * @return array{customer: Customer, subscription: Subscription, invoice: Invoice, payment: Payment}
     */
    private function createCustomerWithSubscription(
        Tenant $tenant,
        Plan $plan,
        Router $router,
        IpPool $pool,
        string $customerCode,
        string $customerName,
        string $customerEmail,
        string $subscriptionCode,
        string $invoiceNumber,
        string $ipAddress,
    ): array {
        $customerParts = explode(' ', $customerName, 2);
        $firstName = $customerParts[0] ?? $customerName;
        $lastName = $customerParts[1] ?? $customerParts[0] ?? $customerName;

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => $customerCode,
            'customer_type' => 'individual',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $customerName,
            'email' => $customerEmail,
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => $ipAddress,
            'status' => 'assigned',
            'customer_id' => $customer->id,
            'mac_address' => 'AA:BB:CC:DD:EE:'.substr($subscriptionCode, -2),
            'subscription_code' => $subscriptionCode,
            'assigned_at' => now(),
        ]);

        $pool->updateStatistics();

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => $subscriptionCode,
            'name' => $customerName.' Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Head Office',
            'connection_type' => 'static',
            'ip_address' => $ipAddress,
            'ip_pool_id' => $pool->id,
            'ip_management' => 'system',
            'pppoe_username' => strtolower(str_replace(' ', '.', $customerName)),
            'pppoe_password' => 'secret',
            'base_price' => $plan->price,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => $plan->price,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'grace_period_days' => 7,
            'status' => 'active',
            'start_date' => now(),
            'activation_date' => now(),
            'next_billing_date' => now()->addMonth()->toDateString(),
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $invoiceNumber,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(25)->toDateString(),
            'subtotal' => $plan->price,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $plan->price,
            'paid_amount' => 25,
            'balance_due' => $plan->price - 25,
            'status' => 'partially_paid',
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-'.substr($subscriptionCode, -4),
            'amount' => 25,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        return [
            'customer' => $customer,
            'subscription' => $subscription,
            'invoice' => $invoice,
            'payment' => $payment,
        ];
    }

    private function bindFakeRouterOsClient(): void
    {
        $this->app->instance(RouterOsClient::class, new class extends RouterOsClient
        {
            public function execute(Router $router, callable $callback): mixed
            {
                return $callback(null, $this);
            }

            public function writeSentence($connection, array $words): void {}

            public function readResponse($connection): array
            {
                return [];
            }
        });
    }
}
