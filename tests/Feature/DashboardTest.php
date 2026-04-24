<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_real_tenant_scoped_metrics(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $otherTenant = $this->createTenant('beta-link', 'BetaLink Fiber');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Business Fiber 200',
            'internal_name' => 'business_fiber_200',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 149.99,
        ]);

        $otherPlan = Plan::factory()->create([
            'name' => 'Private Metro 1G',
            'internal_name' => 'private_metro_1g',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 399.99,
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha Core Router',
            'site' => 'Downtown POP',
            'active_sessions_count' => 24,
            'cpu_usage' => 37,
            'memory_usage' => 41,
        ]);

        Router::factory()->offline()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha Edge Router',
            'site' => 'North Tower',
        ]);

        Router::factory()->online()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Beta Backbone Router',
            'site' => 'Foreign POP',
            'active_sessions_count' => 99,
        ]);

        $firstCustomer = $this->createCustomer($tenant, [
            'name' => 'Acme Industries',
            'company_name' => 'Acme Industries',
            'customer_type' => 'business',
            'balance' => 250,
            'credit_limit' => 100,
        ]);

        $secondCustomer = $this->createCustomer($tenant, [
            'name' => 'Jane Doe',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'balance' => 50,
            'credit_limit' => 100,
        ]);

        $otherCustomer = $this->createCustomer($otherTenant, [
            'name' => 'Should Stay Hidden',
            'company_name' => 'Should Stay Hidden',
            'customer_type' => 'business',
        ]);

        $this->createSubscription($tenant, $firstCustomer, $plan, $router, [
            'subscription_code' => 'SUB-ALPHA-001',
            'status' => 'active',
            'connection_type' => 'pppoe',
            'total_price' => 149.99,
        ]);

        $this->createSubscription($tenant, $secondCustomer, $plan, $router, [
            'subscription_code' => 'SUB-ALPHA-002',
            'status' => 'pending',
            'connection_type' => 'dhcp',
            'total_price' => 89.99,
        ]);

        $this->createSubscription($tenant, $secondCustomer, $plan, $router, [
            'subscription_code' => 'SUB-ALPHA-003',
            'status' => 'suspended',
            'connection_type' => 'static',
            'total_price' => 59.99,
        ]);

        $this->createSubscription($otherTenant, $otherCustomer, $otherPlan, null, [
            'subscription_code' => 'SUB-BETA-001',
            'status' => 'active',
            'connection_type' => 'pppoe',
            'total_price' => 399.99,
        ]);

        IpPool::factory()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'name' => 'Alpha Pool',
            'network_address' => '10.20.30.0',
            'cidr' => 24,
            'total_ips' => 100,
            'used_ips' => 85,
            'reserved_ips' => 5,
            'available_ips' => 10,
            'status' => 'active',
        ]);

        IpPool::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Beta Exhausted Pool',
            'network_address' => '10.99.0.0',
            'cidr' => 24,
            'total_ips' => 100,
            'used_ips' => 100,
            'reserved_ips' => 0,
            'available_ips' => 0,
            'status' => 'exhausted',
        ]);

        ActivityLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'action' => 'user.created',
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        ActivityLog::create([
            'tenant_id' => $otherTenant->id,
            'action' => 'user.deleted',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('AlphaNet Communications');
        $response->assertSee('Business Fiber 200');
        $response->assertSee('Alpha Core Router');
        $response->assertSee('$149.99');
        $response->assertDontSee('Should Stay Hidden');
        $response->assertDontSee('Private Metro 1G');
        $response->assertViewHas('dashboard', function (array $dashboard): bool {
            return $dashboard['stats']['customers']['value'] === 2
                && $dashboard['stats']['subscriptions']['value'] === 1
                && $dashboard['attention'][0]['value'] === 1
                && $dashboard['attention'][1]['value'] === 1
                && $dashboard['attention'][2]['value'] === 1
                && $dashboard['attention'][3]['value'] === 1
                && $dashboard['attention'][4]['value'] === 1
                && $dashboard['popular_plans']->first()['name'] === 'Business Fiber 200'
                && $dashboard['recent_activity']->first()['title'] === 'User Created';
        });
    }

    public function test_dashboard_shows_setup_empty_state_for_new_tenant(): void
    {
        $tenant = $this->createTenant('green-field', 'Green Field Networks');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Your tenant is ready for setup');
        $response->assertViewHas('dashboard', function (array $dashboard): bool {
            return $dashboard['empty_state']['show'] === true
                && $dashboard['stats']['customers']['value'] === 0
                && $dashboard['stats']['subscriptions']['value'] === 0;
        });
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

    private function createCustomer(Tenant $tenant, array $overrides = []): Customer
    {
        $defaults = [
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'company_name' => null,
            'name' => 'John Smith',
            'email' => Str::lower(Str::random(10)).'@example.com',
            'phone' => '555-0100',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'state' => 'CA',
            'postal_code' => '90210',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'prepaid',
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ];

        return Customer::create(array_merge($defaults, $overrides));
    }

    private function createSubscription(
        Tenant $tenant,
        Customer $customer,
        Plan $plan,
        ?Router $router,
        array $overrides = [],
    ): Subscription {
        $defaults = [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(8)),
            'plan_id' => $plan->id,
            'router_id' => $router?->id,
            'connection_type' => 'pppoe',
            'base_price' => 99.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 99.99,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
            'start_date' => now(),
        ];

        return Subscription::create(array_merge($defaults, $overrides));
    }
}
