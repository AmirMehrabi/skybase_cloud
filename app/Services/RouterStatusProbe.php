<?php

namespace App\Services;

use App\Models\Router;
use App\Services\Monitoring\PingProbe;

class RouterStatusProbe
{
    public function __construct(
        private PingProbe $pingProbe,
    ) {}

    /**
     * @return array{online: bool, endpoint: string, latency_ms: float|null, error: string|null, method: string}
     */
    public function check(Router $router): array
    {
        $host = (string) $router->ip_address;
        $port = (int) ($router->api_port ?: $router->ssh_port ?: 22);
        $timeout = max(1.0, min((float) config('monitoring.router_status_tcp_timeout_seconds'), 5.0));
        $endpoint = "{$host}:{$port}";
        $startedAt = microtime(true);
        $errorNumber = 0;
        $errorMessage = '';

        $connection = @fsockopen($host, $port, $errorNumber, $errorMessage, $timeout);

        if (is_resource($connection)) {
            fclose($connection);

            return [
                'online' => true,
                'endpoint' => $endpoint,
                'latency_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'error' => null,
                'method' => 'tcp',
            ];
        }

        $tcpError = trim($errorMessage) !== '' ? "{$errorMessage} ({$errorNumber})" : "Unable to connect to {$endpoint}";
        $ping = $this->pingProbe->check(
            $host,
            1,
            max(1, (int) config('monitoring.router_status_ping_timeout_seconds')),
        );

        if ($ping['online']) {
            return [
                'online' => true,
                'endpoint' => $endpoint,
                'latency_ms' => $ping['latency_ms'],
                'error' => "Management port unavailable: {$tcpError}; ping reachable.",
                'method' => 'ping',
            ];
        }

        return [
            'online' => false,
            'endpoint' => $endpoint,
            'latency_ms' => null,
            'error' => trim((string) $ping['error']) !== '' ? "{$tcpError}; {$ping['error']}" : $tcpError,
            'method' => 'tcp+ping',
        ];
    }
}
