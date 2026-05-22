<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Tenant;
use App\Services\RouterStatusProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class RouterStatusCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_checks_due_monitored_routers_and_updates_statuses(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $dueOnlineRouter = Router::factory()->offline()->create([
            'tenant_id' => $tenant->id,
            'ip_address' => '192.0.2.10',
            'last_status_checked_at' => now()->subMinutes(6),
        ]);
        $dueOfflineRouter = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'ip_address' => '192.0.2.11',
            'last_status_checked_at' => now()->subMinutes(10),
        ]);
        $recentRouter = Router::factory()->offline()->create([
            'tenant_id' => $tenant->id,
            'ip_address' => '192.0.2.12',
            'last_status_checked_at' => now()->subMinutes(4),
        ]);
        $disabledRouter = Router::factory()->offline()->create([
            'tenant_id' => $tenant->id,
            'ip_address' => '192.0.2.13',
            'enable_monitoring' => false,
        ]);

        $this->mock(RouterStatusProbe::class)
            ->shouldReceive('check')
            ->twice()
            ->andReturn(
                [
                    'online' => true,
                    'endpoint' => '192.0.2.10:8728',
                    'latency_ms' => 12.44,
                    'error' => null,
                ],
                [
                    'online' => false,
                    'endpoint' => '192.0.2.11:8728',
                    'latency_ms' => null,
                    'error' => 'Connection timed out (110)',
                ],
            );

        Log::spy();

        $this->artisan('routers:check-status')
            ->expectsOutputToContain('Checked: 2')
            ->assertExitCode(0);

        $this->assertSame('online', $dueOnlineRouter->fresh()->status);
        $this->assertNotNull($dueOnlineRouter->fresh()->last_status_checked_at);
        $this->assertNotNull($dueOnlineRouter->fresh()->last_status_changed_at);
        $this->assertNull($dueOnlineRouter->fresh()->status_check_error);

        $this->assertSame('offline', $dueOfflineRouter->fresh()->status);
        $this->assertSame('Connection timed out (110)', $dueOfflineRouter->fresh()->status_check_error);
        $this->assertNotNull($dueOfflineRouter->fresh()->last_status_changed_at);

        $this->assertSame('offline', $recentRouter->fresh()->status);
        $this->assertNull($recentRouter->fresh()->last_status_changed_at);
        $this->assertSame('offline', $disabledRouter->fresh()->status);
        $this->assertNull($disabledRouter->fresh()->last_status_checked_at);
    }

    public function test_it_marks_router_offline_and_logs_when_probe_fails(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'ip_address' => '192.0.2.20',
            'last_status_checked_at' => now()->subMinutes(6),
        ]);

        $this->mock(RouterStatusProbe::class)
            ->shouldReceive('check')
            ->once()
            ->andThrow(new RuntimeException('Probe crashed'));

        Log::spy();

        $this->artisan('routers:check-status')
            ->expectsOutputToContain('failed: 1')
            ->assertExitCode(1);

        $router->refresh();

        $this->assertSame('offline', $router->status);
        $this->assertSame('Probe crashed', $router->status_check_error);
        $this->assertNotNull($router->last_status_checked_at);
        $this->assertNotNull($router->last_status_changed_at);
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
