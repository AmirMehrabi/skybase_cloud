<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class IpdrRouterBridgeTest extends TestCase
{
    use RefreshDatabase;

    private function router(): Router
    {
        config(['ipdr.enabled' => true, 'ipdr.url' => 'https://ipdr.example', 'ipdr.integration_token' => str_repeat('s', 48)]);
        $tenant = Tenant::create(['id' => (string) Str::uuid(), 'name' => 'ISP', 'slug' => 'bridge', 'company_name' => 'ISP', 'email' => 'bridge@example.com', 'timezone' => 'UTC', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->actingAs($user);

        return Router::factory()->create(['tenant_id' => $tenant->id, 'vendor' => 'Mikrotik', 'netflow_enabled' => true]);
    }

    public function test_router_packet_test_reads_ipdr_instead_of_local_flows(): void
    {
        $router = $this->router();
        Http::fake(['ipdr.example/*' => Http::response(['last_received_at' => now()->toIso8601String()])]);
        $this->postJson(route('routers.netflow.test', $router))->assertOk()->assertJsonPath('status', 'received');
        $this->assertSame('received', $router->fresh()->netflow_test_status);
        Http::assertSent(fn ($request) => $request->hasHeader('X-Skybase-Tenant', (string) $router->tenant_id) && $request['router_id'] === (string) $router->id);
    }

    public function test_router_summary_uses_ipdr_and_handles_an_outage(): void
    {
        $router = $this->router();
        Http::fake(['ipdr.example/*' => Http::response(['stats' => ['flows' => 3], 'latest_flows' => []])]);
        $this->getJson(route('routers.netflow.data', $router))->assertOk()->assertJsonPath('stats.flows', 3);
    }

    public function test_router_cannot_request_v5_or_sampled_metering_in_ipdr_mode(): void
    {
        $router = $this->router();
        $this->postJson(route('routers.netflow.setup', $router), ['netflow_enabled' => true, 'netflow_collector_host' => '192.0.2.1', 'netflow_collector_port' => 2055, 'netflow_version' => 5, 'netflow_interfaces' => 'all', 'netflow_sampling_interval' => 10])->assertUnprocessable()->assertJsonValidationErrors(['netflow_version', 'netflow_sampling_interval']);
    }
}
