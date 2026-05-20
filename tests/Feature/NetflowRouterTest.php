<?php

namespace Tests\Feature;

use App\Models\NetflowFlow;
use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RouterOs\RouterOsTrafficFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NetflowRouterTest extends TestCase
{
    use RefreshDatabase;

    public function test_mikrotik_router_netflow_can_be_configured(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'vendor' => 'Mikrotik',
            'api_username' => 'admin',
            'api_password' => 'secret',
        ]);

        $this->mock(RouterOsTrafficFlowService::class)
            ->shouldReceive('configure')
            ->once()
            ->andReturn(['ok' => true]);

        $response = $this->actingAs($user)->postJson(route('routers.netflow.setup', $router), [
            'netflow_enabled' => true,
            'netflow_collector_host' => '10.10.10.10',
            'netflow_collector_port' => 2055,
            'netflow_version' => 9,
            'netflow_interfaces' => 'all',
            'netflow_sampling_interval' => 1,
        ]);

        $response->assertOk();

        $router->refresh();

        $this->assertTrue($router->netflow_enabled);
        $this->assertSame('10.10.10.10', $router->netflow_collector_host);
        $this->assertSame('configured', $router->netflow_setup_status);
    }

    public function test_non_mikrotik_router_cannot_be_configured_for_netflow(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'vendor' => 'Cisco',
        ]);

        $response = $this->actingAs($user)->postJson(route('routers.netflow.setup', $router), [
            'netflow_enabled' => true,
            'netflow_collector_host' => '10.10.10.10',
            'netflow_collector_port' => 2055,
            'netflow_version' => 9,
            'netflow_interfaces' => 'all',
            'netflow_sampling_interval' => 1,
        ]);

        $response->assertUnprocessable();
    }

    public function test_netflow_test_reports_recent_packets(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'vendor' => 'Mikrotik',
            'netflow_enabled' => true,
        ]);

        NetflowFlow::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'exporter_ip' => $router->ip_address,
            'source_ip' => '192.0.2.10',
            'destination_ip' => '198.51.100.10',
            'source_port' => 12345,
            'destination_port' => 443,
            'protocol' => 6,
            'bytes' => 4096,
            'packets' => 12,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('routers.netflow.test', $router));

        $response->assertOk()
            ->assertJsonPath('status', 'received');

        $this->assertSame('received', $router->fresh()->netflow_test_status);
    }

    public function test_netflow_data_is_router_and_tenant_scoped(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $otherTenant = $this->createTenant('beta-net');
        $router = Router::factory()->create([
            'tenant_id' => $tenant->id,
            'vendor' => 'Mikrotik',
            'netflow_enabled' => true,
        ]);
        $otherRouter = Router::factory()->create([
            'tenant_id' => $otherTenant->id,
            'vendor' => 'Mikrotik',
            'netflow_enabled' => true,
        ]);

        NetflowFlow::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'exporter_ip' => $router->ip_address,
            'source_ip' => '192.0.2.10',
            'destination_ip' => '198.51.100.10',
            'protocol' => 17,
            'bytes' => 1000,
            'packets' => 10,
            'received_at' => now(),
        ]);
        NetflowFlow::query()->withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'router_id' => $otherRouter->id,
            'exporter_ip' => $otherRouter->ip_address,
            'source_ip' => '203.0.113.10',
            'destination_ip' => '198.51.100.20',
            'protocol' => 6,
            'bytes' => 9000,
            'packets' => 90,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('routers.netflow.data', $router));

        $response->assertOk()
            ->assertJsonPath('stats.flows', 1)
            ->assertJsonPath('stats.bytes', 1000)
            ->assertJsonMissing(['label' => '203.0.113.10']);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function tenantUser(): array
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);

        return [$tenant, $user];
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => "{$slug}@example.com",
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
