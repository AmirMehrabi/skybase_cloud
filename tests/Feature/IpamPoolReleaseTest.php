<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpamPoolReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_endpoint_releases_an_assigned_ip_and_clears_the_related_subscription(): void
    {
        [$tenant, $user, $pool, $ipAddress, $subscription] = $this->createAssignedIpFixture();

        $response = $this->actingAs($user)->patch(route('ipam.pools.ip-addresses.release', [$pool, $ipAddress]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'IP 165.73.238.160 has been released.');

        $ipAddress->refresh();
        $subscription->refresh();
        $pool->refresh();

        $this->assertSame('available', $ipAddress->status);
        $this->assertNull($ipAddress->customer_id);
        $this->assertNull($ipAddress->subscription_code);
        $this->assertNull($ipAddress->assigned_at);
        $this->assertSame('165.73.238.160', $ipAddress->ip_address);

        $this->assertNull($subscription->ip_address);
        $this->assertSame(0, $pool->used_ips);
        $this->assertSame(1, $pool->available_ips);
        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '165.73.238.160',
            'status' => 'available',
            'customer_id' => null,
            'subscription_code' => null,
        ]);
    }

    public function test_release_endpoint_releases_an_orphaned_ip_even_if_the_subscription_was_deleted_first(): void
    {
        [$tenant, $user, $pool, $ipAddress, $subscription] = $this->createAssignedIpFixture();

        $subscription->delete();

        $response = $this->actingAs($user)->patch(route('ipam.pools.ip-addresses.release', [$pool, $ipAddress]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'IP 165.73.238.160 has been released.');

        $ipAddress->refresh();
        $pool->refresh();

        $this->assertSame('available', $ipAddress->status);
        $this->assertNull($ipAddress->customer_id);
        $this->assertNull($ipAddress->subscription_code);
        $this->assertNull($ipAddress->assigned_at);
        $this->assertSame(0, $pool->used_ips);
        $this->assertSame(1, $pool->available_ips);

        $this->assertSoftDeleted('subscriptions', [
            'id' => $subscription->id,
        ]);
    }

    private function createAssignedIpFixture(): array
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $router = $this->createRouter($tenant, 'Core Router');
        $customer = $this->createCustomer($tenant, 'Amina', 'Hassan');
        $plan = Plan::factory()->create([
            'status' => 'active',
            'name' => 'Fiber 100',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);

        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'name' => 'Release Pool',
            'router_id' => $router->id,
            'network_address' => '165.73.238.0',
            'cidr' => 24,
            'gateway' => '165.73.238.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'type' => 'mixed',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'total_ips' => 1,
            'used_ips' => 1,
            'reserved_ips' => 0,
            'available_ips' => 0,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-TEST-0001',
            'name' => 'Main Subscription',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Downtown Office',
            'connection_type' => 'pppoe',
            'ip_address' => '165.73.238.160',
            'ip_pool_id' => $pool->id,
            'ip_management' => 'system',
            'pppoe_username' => 'aminahassan',
            'pppoe_password' => 'secret',
            'base_price' => 79.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 79.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'grace_period_days' => 0,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $ipAddress = IpAddress::create([
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '165.73.238.160',
            'status' => 'assigned',
            'customer_id' => $customer->id,
            'subscription_code' => $subscription->subscription_code,
            'assigned_at' => now(),
            'released_at' => null,
            'notes' => null,
            'metadata' => null,
        ]);

        $pool->updateStatistics();

        return [$tenant, $user, $pool, $ipAddress, $subscription];
    }

    private function createTenant(string $slug, string $companyName): Tenant
    {
        return Tenant::create([
            'id' => $slug,
            'name' => $companyName,
            'slug' => $slug,
            'company_name' => $companyName,
            'email' => 'hello@'.str_replace('_', '-', $slug).'.test',
            'country' => 'United States',
            'timezone' => 'Asia/Tehran',
            'status' => 'active',
        ]);
    }

    private function createTenantUser(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    private function createRouter(Tenant $tenant, string $name): Router
    {
        return Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'vendor' => 'Mikrotik',
            'enable_provisioning' => true,
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);
    }

    private function createCustomer(Tenant $tenant, string $firstName, string $lastName): Customer
    {
        return Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => Customer::generateCustomerCode(),
            'customer_type' => 'individual',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $firstName.' '.$lastName,
            'email' => strtolower($firstName.'.'.$lastName).'@example.test',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'country' => 'United States',
            'city' => 'Springfield',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'password' => 'password123',
        ]);
    }
}
