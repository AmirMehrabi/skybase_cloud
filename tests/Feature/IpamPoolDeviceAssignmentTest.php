<?php

namespace Tests\Feature;

use App\Models\IpPool;
use App\Models\Router;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpamPoolDeviceAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_shows_site_relationship_and_multi_device_controls(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $site = $this->createSite($tenant, 'Downtown Office', 'DOWNTOWN');
        $firstRouter = $this->createRouter($tenant, 'Core Router');
        $secondRouter = $this->createRouter($tenant, 'Edge Router');

        $response = $this->actingAs($user)->get(route('ipam.pools.create'));

        $response->assertOk();
        $response->assertSee('Device Assignment');
        $response->assertSee($site->name);
        $response->assertSee($firstRouter->name);
        $response->assertSee($secondRouter->name);
        $response->assertSee('All current and future devices');
    }

    public function test_store_persists_site_relationship_and_multiple_device_assignments(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $site = $this->createSite($tenant, 'Downtown Office', 'DOWNTOWN');
        $firstRouter = $this->createRouter($tenant, 'Core Router');
        $secondRouter = $this->createRouter($tenant, 'Edge Router');

        $response = $this->actingAs($user)->post(route('ipam.pools.store'), [
            'name' => 'Campus Pool',
            'site_id' => $site->id,
            'router_ids' => [$firstRouter->id, $secondRouter->id],
            'type' => 'mixed',
            'network_address' => '10.10.0.0',
            'cidr' => 24,
            'gateway' => '10.10.0.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'vlan_id' => 100,
            'allow_static' => '1',
            'auto_assign' => '1',
            'block_reserved' => '0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'IP pool created successfully.');

        $pool = IpPool::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Campus Pool')
            ->firstOrFail();

        $this->assertSame($site->id, $pool->site_id);
        $this->assertSame($site->name, $pool->site);
        $this->assertFalse($pool->all_devices);
        $this->assertSame($firstRouter->id, $pool->router_id);

        $this->assertDatabaseHas('ip_pool_router', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'router_id' => $firstRouter->id,
        ]);

        $this->assertDatabaseHas('ip_pool_router', [
            'tenant_id' => $tenant->id,
            'ip_pool_id' => $pool->id,
            'router_id' => $secondRouter->id,
        ]);

        $this->assertCount(2, $pool->fresh()->routers);
    }

    public function test_store_can_attach_a_pool_to_all_current_and_future_devices(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $site = $this->createSite($tenant, 'Downtown Office', 'DOWNTOWN');

        $response = $this->actingAs($user)->post(route('ipam.pools.store'), [
            'name' => 'Universal Pool',
            'site_id' => $site->id,
            'all_devices' => '1',
            'type' => 'mixed',
            'network_address' => '10.20.0.0',
            'cidr' => 24,
            'gateway' => '10.20.0.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'vlan_id' => 200,
            'allow_static' => '1',
            'auto_assign' => '1',
            'block_reserved' => '0',
        ]);

        $response->assertRedirect();

        $pool = IpPool::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Universal Pool')
            ->firstOrFail();

        $this->assertTrue($pool->all_devices);
        $this->assertNull($pool->router_id);
        $this->assertSame('All current and future devices', $pool->device_summary);
        $this->assertDatabaseMissing('ip_pool_router', [
            'ip_pool_id' => $pool->id,
        ]);
    }

    public function test_show_page_still_supports_legacy_site_text_and_primary_router_data(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = $this->createTenantUser($tenant);
        $router = $this->createRouter($tenant, 'Core Router');

        $pool = IpPool::create([
            'tenant_id' => $tenant->id,
            'name' => 'Legacy Pool',
            'router_id' => $router->id,
            'site' => 'Legacy Office',
            'network_address' => '10.30.0.0',
            'cidr' => 24,
            'gateway' => '10.30.0.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'vlan_id' => 300,
            'type' => 'mixed',
            'status' => 'active',
            'allow_static' => true,
            'auto_assign' => true,
            'block_reserved' => false,
            'total_ips' => 254,
            'used_ips' => 10,
            'reserved_ips' => 4,
            'available_ips' => 240,
        ]);

        $response = $this->actingAs($user)->get(route('ipam.pools.show', $pool));

        $response->assertOk();
        $response->assertSee('Legacy Office');
        $response->assertSee('Core Router');
        $response->assertSee('Legacy Pool');
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

    private function createSite(Tenant $tenant, string $name, string $code): Site
    {
        return Site::create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'name' => $name,
            'description' => "{$name} description",
            'address' => '123 Network Street',
            'latitude' => '35.6892000',
            'longitude' => '51.3890000',
            'status' => 'active',
        ]);
    }

    private function createRouter(Tenant $tenant, string $name): Router
    {
        return Router::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'status' => 'online',
        ]);
    }
}
