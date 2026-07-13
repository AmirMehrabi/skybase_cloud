<?php

namespace App\Services\Monitoring;

use App\Models\Router;
use App\Services\RouterOs\RouterOsClient;

class RouterOsMonitoringService
{
    public function __construct(
        private RouterOsClient $client,
    ) {}

    /**
     * @return array{uptime: string|null, version: string|null, cpu_usage: int|null, memory_usage: int|null, active_sessions_count: int|null}
     */
    public function resources(Router $router): array
    {
        return $this->client->execute($router, function ($connection, RouterOsClient $client): array {
            $client->writeSentence($connection, ['/system/resource/print']);
            $resource = $client->readResponse($connection)[0] ?? [];

            $client->writeSentence($connection, ['/ppp/active/print', '=.proplist=.id']);
            $activeSessions = $client->readResponse($connection);

            return [
                'uptime' => $resource['uptime'] ?? null,
                'version' => $resource['version'] ?? null,
                'cpu_usage' => isset($resource['cpu-load']) ? (int) $resource['cpu-load'] : null,
                'memory_usage' => $this->memoryUsagePercent($resource),
                'active_sessions_count' => count($activeSessions),
            ];
        });
    }

    public function activePppInterface(Router $router, string $username, ?int $timeoutSeconds = null): ?string
    {
        return $this->client->execute($router, function ($connection, RouterOsClient $client) use ($username): ?string {
            $client->writeSentence($connection, [
                '/ppp/active/print',
                '=.proplist=name,interface,service,address',
                '?name='.$username,
            ]);

            $session = $client->readResponse($connection)[0] ?? null;

            return $session['interface'] ?? null;
        }, $timeoutSeconds);
    }

    /**
     * @return array{rx_bps: int, tx_bps: int, source: string}
     */
    public function interfaceTraffic(Router $router, string $interface, ?int $timeoutSeconds = null): array
    {
        return $this->client->execute($router, function ($connection, RouterOsClient $client) use ($interface): array {
            $client->writeSentence($connection, [
                '/interface/monitor-traffic',
                '=interface='.$interface,
                '=once=',
            ]);

            $traffic = $client->readResponse($connection)[0] ?? [];

            return [
                'rx_bps' => (int) ($traffic['rx-bits-per-second'] ?? 0),
                'tx_bps' => (int) ($traffic['tx-bits-per-second'] ?? 0),
                'source' => 'routeros',
            ];
        }, $timeoutSeconds);
    }

    /**
     * @param  array<string, string>  $resource
     */
    private function memoryUsagePercent(array $resource): ?int
    {
        $free = isset($resource['free-memory']) ? (int) $resource['free-memory'] : null;
        $total = isset($resource['total-memory']) ? (int) $resource['total-memory'] : null;

        if (! $free || ! $total || $total <= 0) {
            return null;
        }

        return max(0, min(100, (int) round((($total - $free) / $total) * 100)));
    }
}
