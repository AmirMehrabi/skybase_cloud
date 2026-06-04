<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionIndexDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriptions_index_data_includes_pppoe_credentials_and_connection_status(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-SEARCH-0001',
            'customer_type' => 'individual',
            'first_name' => 'Alpha',
            'last_name' => 'User',
            'name' => 'Alpha User',
            'email' => 'alpha.search@example.com',
            'mobile' => '555-0001',
            'address_line1' => '123 Network Street',
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
            'status' => 'active',
        ]);

        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'online',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'alpha.user',
            'pppoe_password' => 'secret-pass',
            'base_price' => 50,
            'total_price' => 50,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->getJson(route('subscriptions.data'));

        $response->assertOk();
        $response->assertJsonPath('subscriptions.0.pppoe_username', 'alpha.user');
        $response->assertJsonPath('subscriptions.0.pppoe_password', 'secret-pass');
        $response->assertJsonPath('subscriptions.0.connection_status', 'offline');
        $response->assertJsonPath('subscriptions.0.connection_type', 'pppoe');
    }

    public function test_subscriptions_index_search_matches_partial_pppoe_username(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-SEARCH-0002',
            'customer_type' => 'individual',
            'first_name' => 'Beta',
            'last_name' => 'User',
            'name' => 'Beta User',
            'email' => 'beta.search@example.com',
            'mobile' => '555-0002',
            'address_line1' => '456 Network Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create(['status' => 'active']);

        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'online',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'alpha.user',
            'pppoe_password' => 'secret-pass',
            'base_price' => 50,
            'total_price' => 50,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-0002',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'beta.user',
            'pppoe_password' => 'secret-pass-2',
            'base_price' => 60,
            'total_price' => 60,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->getJson(route('subscriptions.data', ['search' => 'alpha']));

        $response->assertOk();
        $this->assertSame(1, $response->json('pagination.total'));
        $response->assertJsonPath('subscriptions.0.pppoe_username', 'alpha.user');
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
}
