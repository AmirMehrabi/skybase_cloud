<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerPortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_and_view_the_portal_dashboard(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant, [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('customer.login.store'), [
            'tenant' => 'alpha-net',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($customer, 'customer');

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Dashboard');
    }

    public function test_customer_portal_layout_uses_tenant_company_name_and_tagline_for_branding(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $tenant->forceFill([
            'company_name' => 'AlphaNet Communications',
            'tagline' => 'Simple customer self care',
        ])->save();

        $customer = $this->createCustomer($tenant);

        $response = $this->actingAs($customer, 'customer')->get(route('customer.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard - AlphaNet Communications', false);
        $response->assertSee('AlphaNet Communications');
        $response->assertSee('Simple customer self care');
    }

    public function test_customer_login_requires_the_matching_tenant(): void
    {
        $alpha = $this->createTenant('alpha-net');
        $beta = $this->createTenant('beta-net');

        $this->createCustomer($alpha, [
            'email' => 'shared@example.com',
            'password' => 'password123',
        ]);

        $this->createCustomer($beta, [
            'email' => 'shared@example.com',
            'password' => 'different123',
        ]);

        $response = $this->post(route('customer.login.store'), [
            'tenant' => 'beta-net',
            'email' => 'shared@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_self_hosted_customer_login_uses_the_first_tenant_without_a_tenant_code(): void
    {
        config()->set('app.cloud.enabled', false);
        config()->set('app.cloud.guest_entry', 'customer');

        $tenant = $this->createTenant('first-tenant');
        $customer = $this->createCustomer($tenant, [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $this->createTenant('second-tenant');

        $this->get(route('customer.login'))
            ->assertOk()
            ->assertDontSee('Tenant code')
            ->assertSee('Sign in with your account email.');

        $response = $this->post(route('customer.login.store'), [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_customer_portal_only_lists_the_authenticated_customers_records(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $customer = $this->createCustomer($tenant, [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);
        $otherCustomer = $this->createCustomer($tenant, [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $plan = Plan::factory()->create(['name' => 'Fiber 100']);

        $ownSubscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-PORTAL-OWN',
            'plan_id' => $plan->id,
            'connection_type' => 'dhcp',
            'base_price' => 100,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 100,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $otherCustomer->id,
            'subscription_code' => 'SUB-PORTAL-OTHER',
            'plan_id' => $plan->id,
            'connection_type' => 'dhcp',
            'base_price' => 100,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 100,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $ownSubscription->id,
            'invoice_number' => 'INV-PORTAL-OWN',
            'billing_period_start' => today()->startOfMonth(),
            'billing_period_end' => today()->endOfMonth(),
            'issue_date' => today(),
            'due_date' => today()->addDays(7),
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'paid_amount' => 0,
            'balance_due' => 100,
            'status' => 'issued',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $otherCustomer->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-PORTAL-OTHER',
            'billing_period_start' => today()->startOfMonth(),
            'billing_period_end' => today()->endOfMonth(),
            'issue_date' => today(),
            'due_date' => today()->addDays(7),
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'paid_amount' => 0,
            'balance_due' => 100,
            'status' => 'issued',
        ]);

        $this->actingAs($customer, 'customer');

        $this->get(route('customer.subscriptions.index'))
            ->assertOk()
            ->assertSee('SUB-PORTAL-OWN')
            ->assertDontSee('SUB-PORTAL-OTHER');

        $this->get(route('customer.invoices.index'))
            ->assertOk()
            ->assertSee('INV-PORTAL-OWN')
            ->assertDontSee('INV-PORTAL-OTHER');
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => str($slug)->headline()->toString(),
            'slug' => $slug,
            'company_name' => str($slug)->headline()->toString(),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCustomer(Tenant $tenant, array $overrides = []): Customer
    {
        return Customer::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_code' => Customer::generateCustomerCode(),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
        ], $overrides));
    }
}
