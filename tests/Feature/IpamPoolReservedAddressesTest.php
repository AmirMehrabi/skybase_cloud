<?php

namespace Tests\Feature;

use App\Models\IpPool;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpamPoolReservedAddressesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_reserves_additional_unused_ip_addresses_when_creating_a_pool(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $router = $this->createRouter($tenant, 'Core Router');

        $response = $this->actingAs($user)->post(route('ipam.pools.store'), [
            'name' => 'Reserved Pool',
            'router_ids' => [$router->id],
            'type' => 'mixed',
            'network_address' => '10.60.0.0',
            'cidr' => 24,
            'gateway' => '10.60.0.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'allow_static' => '1',
            'auto_assign' => '1',
            'block_reserved' => '0',
            'reserved_addresses' => "10.60.0.10\n10.60.0.11",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'IP pool created successfully.');

        $pool = IpPool::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Reserved Pool')
            ->firstOrFail();

        $this->assertSame(4, $pool->fresh()->reserved_ips);
        $this->assertSame(250, $pool->fresh()->available_ips);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.60.0.10',
            'status' => 'reserved',
            'notes' => 'Custom reservation',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.60.0.11',
            'status' => 'reserved',
            'notes' => 'Custom reservation',
        ]);
    }

    public function test_update_syncs_custom_reserved_ip_addresses_and_releases_removed_ones(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $router = $this->createRouter($tenant, 'Core Router');

        $pool = $this->createPool($tenant, $router, '10.61.0.0');

        $this->actingAs($user)->put(route('ipam.pools.update', $pool), [
            'name' => 'Reserved Pool',
            'router_ids' => [$router->id],
            'type' => 'mixed',
            'status' => 'active',
            'network_address' => '10.61.0.0',
            'cidr' => 24,
            'gateway' => '10.61.0.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'allow_static' => '1',
            'auto_assign' => '1',
            'block_reserved' => '0',
            'reserved_addresses' => '10.61.0.11',
        ])->assertRedirect(route('ipam.pools.show', $pool));

        $pool = $pool->fresh();

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.61.0.10',
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'ip_address' => '10.61.0.11',
            'status' => 'reserved',
            'notes' => 'Custom reservation',
        ]);

        $this->assertSame(3, $pool->reserved_ips);
    }

    public function test_check_ip_treats_reserved_ip_as_unavailable(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $router = $this->createRouter($tenant, 'Core Router');

        $pool = $this->createPool($tenant, $router, '10.62.0.0', '10.62.0.10');

        $response = $this->actingAs($user)->getJson('/ipam/check-ip?ip=10.62.0.10');

        $response->assertOk();
        $response->assertJson([
            'available' => false,
            'ip' => '10.62.0.10',
            'status' => 'reserved',
            'message' => 'IP is reserved',
        ]);

        $this->assertSame(3, $pool->fresh()->reserved_ips);
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

    private function createPool(Tenant $tenant, Router $router, string $networkAddress, ?string $reservedAddresses = null): IpPool
    {
        $response = $this->actingAs($this->createTenantUser($tenant))->post(route('ipam.pools.store'), array_filter([
            'name' => 'Seed Pool',
            'router_ids' => [$router->id],
            'type' => 'mixed',
            'network_address' => $networkAddress,
            'cidr' => 24,
            'gateway' => substr($networkAddress, 0, strrpos($networkAddress, '.')).'.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'allow_static' => '1',
            'auto_assign' => '1',
            'block_reserved' => '0',
            'reserved_addresses' => $reservedAddresses,
        ]));

        $response->assertRedirect();

        return IpPool::query()
            ->where('tenant_id', $tenant->id)
            ->where('network_address', $networkAddress)
            ->firstOrFail();
    }
}
