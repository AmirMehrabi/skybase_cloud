<?php

namespace App\Services;

use App\Models\Router;

class RouterStatusProbe
{
    /**
     * @return array{online: bool, endpoint: string, latency_ms: float|null, error: string|null}
     */
    public function check(Router $router): array
    {
        $host = (string) $router->ip_address;
        $port = (int) ($router->api_port ?: $router->ssh_port ?: 22);
        $timeout = max(1, min((int) ($router->timeout ?: 30), 60));
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
            ];
        }

        return [
            'online' => false,
            'endpoint' => $endpoint,
            'latency_ms' => null,
            'error' => trim($errorMessage) !== '' ? "{$errorMessage} ({$errorNumber})" : "Unable to connect to {$endpoint}",
        ];
    }
}
