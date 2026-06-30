<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\RouterMonitoringState;
use App\Models\Subscription;
use App\Models\SubscriptionBandwidthState;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Monitoring\RouterHealthCollector;
use App\Services\Monitoring\RrdToolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MonitoringFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_monitoring_page_is_tenant_scoped(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha-net');
        $otherTenant = $this->createTenant('beta-net');
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha Core',
            'ip_address' => '192.0.2.10',
        ]);
        $otherRouter = Router::factory()->online()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Beta Core',
            'ip_address' => '192.0.2.20',
        ]);

        RouterMonitoringState::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'status' => 'online',
            'latency_ms' => 12.5,
            'packet_loss_percent' => 0,
            'sampled_at' => now(),
        ]);
        RouterMonitoringState::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'router_id' => $otherRouter->id,
            'status' => 'online',
            'latency_ms' => 1.5,
            'packet_loss_percent' => 0,
            'sampled_at' => now(),
        ]);

        $this->mock(RrdToolService::class)
            ->shouldReceive('isAvailable')
            ->once()
            ->andReturn(false);

        $response = $this->actingAs($user)->get(route('network.monitoring'));

        $response->assertOk()
            ->assertSee('Alpha Core')
            ->assertDontSee('Beta Core')
            ->assertSee('RRDTool is not available');
    }

    public function test_network_monitoring_data_returns_aggregated_rrd_series(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha-net');
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);

        $this->mock(RrdToolService::class)
            ->shouldReceive('routerHealthSeries')
            ->once()
            ->withArgs(fn (Router $seriesRouter, string $range): bool => $seriesRouter->id === $router->id && $range === '1h')
            ->andReturn([
                ['timestamp' => 1710000000, 'latency_ms' => 10, 'packet_loss_percent' => 0, 'online' => 1, 'cpu_usage' => 20, 'memory_usage' => 30, 'active_sessions_count' => 4],
                ['timestamp' => 1710000060, 'latency_ms' => 20, 'packet_loss_percent' => 5, 'online' => 1, 'cpu_usage' => 25, 'memory_usage' => 35, 'active_sessions_count' => 5],
            ]);

        $response = $this->actingAs($user)->getJson(route('network.monitoring.data', [
            'router_id' => $router->id,
            'range' => '1h',
        ]));

        $response->assertOk()
            ->assertJsonPath('chartData.0.latency_ms', 10)
            ->assertJsonPath('chartData.1.packet_loss_percent', 5);
    }

    public function test_subscription_live_bandwidth_endpoint_is_tenant_scoped(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha-net');
        $otherTenant = $this->createTenant('beta-net');
        $subscription = $this->subscription($tenant);
        $otherSubscription = $this->subscription($otherTenant);

        SubscriptionBandwidthState::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'router_id' => $subscription->router_id,
            'interface_name' => '<pppoe-alpha.user>',
            'rx_bps' => 12000000,
            'tx_bps' => 3000000,
            'source' => 'routeros',
            'sampled_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('subscriptions.bandwidth.live', $subscription));

        $response->assertOk()
            ->assertJsonPath('rx_bps', 12000000)
            ->assertJsonPath('interface_name', '<pppoe-alpha.user>');

        $this->actingAs($user)
            ->getJson(route('subscriptions.bandwidth.live', $otherSubscription))
            ->assertNotFound();
    }

    public function test_subscription_bandwidth_history_returns_line_chart_data_and_metadata(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha-net');
        $subscription = $this->subscription($tenant);

        SubscriptionBandwidthState::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'router_id' => $subscription->router_id,
            'rx_bps' => 12000000,
            'tx_bps' => 3000000,
            'source' => 'routeros',
            'sampled_at' => now(),
            'last_success_at' => now(),
        ]);

        $this->mock(RrdToolService::class)
            ->shouldReceive('subscriptionBandwidthChartData')
            ->once()
            ->withArgs(fn (Subscription $seriesSubscription, string $range): bool => $seriesSubscription->id === $subscription->id && $range === '24h')
            ->andReturn([
                'chartData' => [
                    ['timestamp' => 1710000000, 'time' => '00:00', 'rx_bps' => 12000000, 'tx_bps' => 3000000],
                    ['timestamp' => 1710000300, 'time' => '00:05', 'rx_bps' => null, 'tx_bps' => null],
                ],
                'hasData' => true,
            ]);

        $this->actingAs($user)
            ->getJson(route('subscriptions.bandwidth.history', [$subscription, 'range' => '24h']))
            ->assertOk()
            ->assertJsonPath('range', '24h')
            ->assertJsonPath('hasData', true)
            ->assertJsonPath('chartData.0.rx_bps', 12000000)
            ->assertJsonPath('chartData.1.rx_bps', null)
            ->assertJsonPath('stale', false)
            ->assertJsonPath('status', 'available');
    }

    public function test_subscription_bandwidth_history_degrades_to_an_empty_graph_payload(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha-net');
        $subscription = $this->subscription($tenant);

        $this->mock(RrdToolService::class)
            ->shouldReceive('subscriptionBandwidthChartData')
            ->once()
            ->andThrow(new \RuntimeException('RRD unavailable'));

        $this->actingAs($user)
            ->getJson(route('subscriptions.bandwidth.history', [$subscription, 'range' => 'invalid']))
            ->assertOk()
            ->assertJsonPath('range', '1h')
            ->assertJsonPath('hasData', false)
            ->assertJsonPath('chartData', [])
            ->assertJsonMissing(['error']);
    }

    public function test_router_health_collection_command_uses_collector_for_due_monitored_routers(): void
    {
        [$tenant] = $this->tenantUser('alpha-net');
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'last_status_checked_at' => now()->subMinutes(2),
        ]);

        $this->mock(RouterHealthCollector::class)
            ->shouldReceive('collect')
            ->once()
            ->withArgs(fn (Router $collectedRouter): bool => $collectedRouter->id === $router->id)
            ->andReturn([]);

        $this->artisan('monitoring:collect-router-health --force')
            ->expectsOutputToContain('Checked: 1')
            ->assertExitCode(0);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function tenantUser(string $slug): array
    {
        $tenant = $this->createTenant($slug);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
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

    private function subscription(Tenant $tenant): Subscription
    {
        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(6)),
            'customer_type' => 'individual',
            'name' => 'Monitoring Customer',
            'first_name' => 'Monitoring',
            'last_name' => 'Customer',
            'email' => Str::random(8).'@example.com',
            'billing_type' => 'prepaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'status' => 'active',
        ]);
        $plan = Plan::factory()->create([
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 50,
        ]);
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'vendor' => 'Mikrotik',
        ]);

        return Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(6)),
            'name' => 'Monitored PPPoE',
            'service_type' => 'pppoe',
            'connection_type' => 'pppoe',
            'pppoe_username' => 'alpha.user',
            'pppoe_password' => 'secret',
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'base_price' => 50,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_price' => 50,
            'status' => 'active',
            'start_date' => now(),
            'activation_date' => now(),
            'next_billing_date' => now()->toDateString(),
        ]);
    }
}
