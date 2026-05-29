<?php

namespace App\Services\RouterOs;

use App\Models\Router;

class RouterOsTrafficFlowService
{
    public function __construct(
        private RouterOsClient $client,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function configure(Router $router): array
    {
        return $this->client->execute($router, function ($connection, RouterOsClient $client) use ($router): array {
            $client->writeSentence($connection, [
                '/ip/traffic-flow/set',
                '=enabled='.($router->netflow_enabled ? 'yes' : 'no'),
                '=interfaces='.($router->netflow_interfaces ?: 'all'),
            ]);
            $client->readResponse($connection);

            if ($router->netflow_enabled) {
                $this->upsertTarget($connection, $router, $client);
            }

            return ['ok' => true];
        });
    }

    /**
     * @param  resource  $connection
     */
    private function upsertTarget($connection, Router $router, RouterOsClient $client): void
    {
        $client->writeSentence($connection, ['/ip/traffic-flow/target/print']);
        $targets = $client->readResponse($connection);
        $targetId = $this->matchingTargetId($targets, (string) $router->netflow_collector_host, (int) $router->netflow_collector_port);
        $sentence = [
            $targetId ? '/ip/traffic-flow/target/set' : '/ip/traffic-flow/target/add',
            '=dst-address='.$router->netflow_collector_host,
            '=port='.(string) $router->netflow_collector_port,
            '=version='.(string) $router->netflow_version,
            '=enabled=yes',
        ];

        if ($targetId) {
            $sentence[] = '=.id='.$targetId;
        }

        $client->writeSentence($connection, $sentence);
        $client->readResponse($connection);
    }

    /**
     * @param  array<int, array<string, string>>  $targets
     */
    private function matchingTargetId(array $targets, string $host, int $port): ?string
    {
        foreach ($targets as $target) {
            if (($target['dst-address'] ?? null) === $host && (int) ($target['port'] ?? 0) === $port) {
                return $target['.id'] ?? null;
            }
        }

        return null;
    }
}
