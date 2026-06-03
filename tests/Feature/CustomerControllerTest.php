<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
