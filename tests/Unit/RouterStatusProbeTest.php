<?php

namespace Tests\Unit;

use App\Models\Router;
use App\Services\Monitoring\PingProbe;
use App\Services\RouterStatusProbe;
use Tests\TestCase;

class RouterStatusProbeTest extends TestCase
{
    public function test_it_treats_router_as_online_when_tcp_fails_but_ping_succeeds(): void
    {
        config([
            'monitoring.router_status_tcp_timeout_seconds' => 1,
            'monitoring.router_status_ping_timeout_seconds' => 1,
        ]);

        $router = new Router([
            'ip_address' => '127.0.0.1',
            'api_port' => 65000,
            'ssh_port' => 22,
        ]);

        $pingProbe = $this->mock(PingProbe::class);
        $pingProbe->shouldReceive('check')
            ->once()
            ->with('127.0.0.1', 1, 1)
            ->andReturn([
                'online' => true,
                'latency_ms' => 1.25,
                'packet_loss_percent' => 0.0,
                'error' => null,
            ]);

        $result = app(RouterStatusProbe::class)->check($router);

        $this->assertTrue($result['online']);
        $this->assertSame('ping', $result['method']);
        $this->assertSame(1.25, $result['latency_ms']);
        $this->assertStringContainsString('Management port unavailable', (string) $result['error']);
    }
}
