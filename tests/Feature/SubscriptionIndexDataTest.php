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

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
            'billing_enabled' => true,
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
