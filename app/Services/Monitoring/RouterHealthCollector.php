<?php

namespace App\Services\Monitoring;

use App\Models\Router;
use App\Models\RouterMonitoringState;
use Throwable;

class RouterHealthCollector
{
    public function __construct(
        private PingProbe $pingProbe,
        private RouterOsMonitoringService $routerOsMonitoring,
        private RrdToolService $rrdTool,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function collect(Router $router): array
    {
        $sampledAt = now();
        $ping = $this->pingProbe->check((string) $router->ip_address);
        $resource = [
            'uptime' => $router->uptime,
            'version' => $router->version,
            'cpu_usage' => $router->cpu_usage,
            'memory_usage' => $router->memory_usage,
            'active_sessions_count' => $router->active_sessions_count,
        ];
        $errors = array_filter([$ping['error']]);

        if ($ping['online'] && $router->isMikrotik() && $router->api_username && $router->api_password) {
            try {
                $resource = [
                    ...$resource,
                    ...$this->routerOsMonitoring->resources($router),
                ];
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        $status = $this->status($ping['online'], $ping['latency_ms'], $ping['packet_loss_percent']);
        $sample = [
            'tenant_id' => (string) $router->tenant_id,
            'router_id' => $router->id,
            'status' => $status,
            'latency_ms' => $ping['latency_ms'],
            'packet_loss_percent' => $ping['packet_loss_percent'],
            'uptime' => $resource['uptime'],
            'cpu_usage' => $resource['cpu_usage'],
            'memory_usage' => $resource['memory_usage'],
            'active_sessions_count' => $resource['active_sessions_count'],
            'sampled_at' => $sampledAt,
            'error' => $errors === [] ? null : implode(' ', $errors),
        ];

        $router->forceFill([
            'status' => $status === 'offline' ? 'offline' : 'online',
            'version' => $resource['version'],
            'uptime' => $resource['uptime'],
            'cpu_usage' => $resource['cpu_usage'] ?? 0,
            'memory_usage' => $resource['memory_usage'] ?? 0,
            'active_sessions_count' => $resource['active_sessions_count'] ?? 0,
            'last_status_checked_at' => $sampledAt,
            'last_status_changed_at' => $router->status !== ($status === 'offline' ? 'offline' : 'online') ? $sampledAt : $router->last_status_changed_at,
            'status_check_error' => $sample['error'],
        ])->save();

        try {
            $this->rrdTool->updateRouterHealth($router, [
                'latency_ms' => $sample['latency_ms'],
                'packet_loss_percent' => $sample['packet_loss_percent'],
                'online' => $status !== 'offline',
                'cpu_usage' => $sample['cpu_usage'],
                'memory_usage' => $sample['memory_usage'],
                'active_sessions_count' => $sample['active_sessions_count'],
            ]);
        } catch (MonitoringStorageUnavailable $exception) {
            $sample['error'] = trim(($sample['error'] ? $sample['error'].' ' : '').'RRDTool: '.$exception->getMessage());
        }

        RouterMonitoringState::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $router->tenant_id, 'router_id' => $router->id],
            $sample,
        );

        return $sample;
    }

    private function status(bool $online, ?float $latencyMs, float $packetLossPercent): string
    {
        if (! $online) {
            return 'offline';
        }

        if (($latencyMs !== null && $latencyMs >= (float) config('monitoring.router_latency_warning_ms'))
            || $packetLossPercent >= (float) config('monitoring.router_packet_loss_warning_percent')) {
            return 'warning';
        }

        return 'online';
    }
}
