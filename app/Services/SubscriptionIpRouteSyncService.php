<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use App\Services\RouterOs\RouterOsClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionIpRouteSyncService
{
    public function __construct(
        private RouterOsClient $routerOs,
    ) {}

    public function syncRoutes(Subscription $subscription): void
    {
        $subscription->loadMissing(['router', 'ipRoutes']);

        Log::info('Subscription IP route sync batch started.', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'router_id' => $subscription->router?->id,
            'route_count' => $subscription->ipRoutes->count(),
        ]);

        $router = $subscription->router;
        $skipReason = $this->skipReason($subscription, $router);
        if ($skipReason !== null) {
            foreach ($subscription->ipRoutes as $route) {
                $this->markSkipped($route, $skipReason);
            }

            Log::info('Subscription IP route sync batch completed.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'router_id' => $router?->id,
                'route_count' => $subscription->ipRoutes->count(),
            ]);

            return;
        }

        try {
            $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($subscription, $router): void {
                foreach ($subscription->ipRoutes as $route) {
                    $this->syncRouteOnConnection($route, $subscription, $router, $connection, $client);
                }
            });
        } catch (Throwable $exception) {
            Log::warning('Subscription IP route sync batch failed.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'router_id' => $router?->id,
                'route_count' => $subscription->ipRoutes->count(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            foreach ($subscription->ipRoutes as $route) {
                $this->markFailed($subscription, $route, $router, $exception);
            }
        }

        Log::info('Subscription IP route sync batch completed.', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'router_id' => $router?->id,
            'route_count' => $subscription->ipRoutes->count(),
        ]);
    }

    public function removeRoutes(Subscription $subscription): void
    {
        $subscription->loadMissing(['router', 'ipRoutes']);

        foreach ($subscription->ipRoutes as $route) {
            $this->removeRoute($route, $subscription);
        }
    }

    public function syncRoute(SubscriptionIpRoute $route, ?Subscription $subscription = null): void
    {
        $subscription ??= $route->subscription;
        $subscription?->loadMissing('router');
        $router = $subscription?->router;

        $skipReason = $this->skipReason($subscription, $router);
        if ($skipReason !== null) {
            $this->markSkipped($route, $skipReason);

            return;
        }

        try {
            $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($route, $subscription, $router): void {
                $this->syncRouteOnConnection($route, $subscription, $router, $connection, $client);
            });
        } catch (Throwable $exception) {
            $this->markFailed($subscription, $route, $router, $exception);
        }
    }

    public function removeRoute(SubscriptionIpRoute $route, ?Subscription $subscription = null): void
    {
        $subscription ??= $route->subscription;
        $subscription?->loadMissing('router');
        $router = $subscription?->router;

        if (! $router || ! $router->isMikrotik() || ! $router->enable_provisioning || ! $router->api_username || ! $router->api_password) {
            return;
        }

        try {
            $comment = $route->routerOsComment();

            Log::info('Subscription IP route removal started.', [
                'tenant_id' => $route->tenant_id,
                'subscription_id' => $route->subscription_id,
                'subscription_ip_route_id' => $route->id,
                'router_id' => $router->id,
                'destination' => $route->destinationAddress(),
                'routeros_route_id' => $route->routeros_route_id,
                'comment' => $comment,
            ]);

            $removedCount = $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($comment, $route): int {
                $client->writeSentence($connection, [
                    '/ip/route/print',
                    $route->routeros_route_id ? '?.id='.$route->routeros_route_id : '?comment='.$comment,
                ]);

                $routes = collect($client->readResponse($connection))
                    ->filter(fn (array $routerRoute): bool => ($routerRoute['.id'] ?? null) === $route->routeros_route_id || ($routerRoute['comment'] ?? null) === $comment)
                    ->values();

                foreach ($routes as $routerRoute) {
                    if (! isset($routerRoute['.id'])) {
                        continue;
                    }

                    $client->writeSentence($connection, [
                        '/ip/route/remove',
                        '=.id='.$routerRoute['.id'],
                    ]);
                    $client->readResponse($connection);
                }

                return $routes->count();
            });

            Log::info('Subscription IP route removal completed.', [
                'tenant_id' => $route->tenant_id,
                'subscription_id' => $route->subscription_id,
                'subscription_ip_route_id' => $route->id,
                'router_id' => $router->id,
                'destination' => $route->destinationAddress(),
                'routeros_route_id' => $route->routeros_route_id,
                'removed_count' => $removedCount,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Subscription IP route removal failed.', [
                'tenant_id' => $route->tenant_id,
                'subscription_id' => $route->subscription_id,
                'subscription_ip_route_id' => $route->id,
                'router_id' => $router->id,
                'destination' => $route->destinationAddress(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $route->forceFill([
                'routeros_sync_status' => 'failed',
                'routeros_sync_error' => 'RouterOS route removal failed: '.$exception->getMessage(),
            ])->save();

            $this->recordActivity($subscription, $route, 'ip_route_removal_failed', 'Subscription IP route removal failed');
        }
    }

    private function skipReason(?Subscription $subscription, ?Router $router): ?string
    {
        if (! $subscription) {
            return 'Subscription is missing.';
        }

        if (! $subscription->isSystemManagedIp()) {
            return 'Subscription does not use system-managed IP assignment.';
        }

        if (blank($subscription->ip_address)) {
            return 'Subscription primary IP is missing.';
        }

        if (! $router) {
            return 'Subscription has no assigned router.';
        }

        if (! $router->isMikrotik()) {
            return 'Assigned router does not support RouterOS IP route sync.';
        }

        if (! $router->enable_provisioning) {
            return 'Router provisioning is disabled.';
        }

        if (! $router->api_username || ! $router->api_password) {
            return 'RouterOS API credentials are missing.';
        }

        return null;
    }

    private function markSkipped(SubscriptionIpRoute $route, string $reason): void
    {
        Log::info('Subscription IP route sync skipped.', [
            'tenant_id' => $route->tenant_id,
            'subscription_id' => $route->subscription_id,
            'subscription_ip_route_id' => $route->id,
            'destination' => $route->destinationAddress(),
            'reason' => $reason,
        ]);

        $route->forceFill([
            'routeros_sync_status' => 'skipped',
            'routeros_sync_error' => $reason,
        ])->save();
    }

    /**
     * @param  resource  $connection
     */
    private function syncRouteOnConnection(SubscriptionIpRoute $route, Subscription $subscription, Router $router, $connection, RouterOsClient $client): void
    {
        try {
            $comment = $route->routerOsComment();
            $destination = $route->destinationAddress();
            $gateway = (string) $subscription->ip_address;

            Log::info('Subscription IP route sync started.', [
                'tenant_id' => $route->tenant_id,
                'subscription_id' => $route->subscription_id,
                'subscription_ip_route_id' => $route->id,
                'router_id' => $router->id,
                'destination' => $destination,
                'gateway' => $gateway,
                'comment' => $comment,
            ]);

            $client->writeSentence($connection, [
                '/ip/route/print',
                '?comment='.$comment,
            ]);

            $existingRoute = collect($client->readResponse($connection))
                ->first(fn (array $routerRoute): bool => ($routerRoute['comment'] ?? null) === $comment);

            if ($existingRoute && isset($existingRoute['.id'])) {
                $client->writeSentence($connection, [
                    '/ip/route/set',
                    '=.id='.$existingRoute['.id'],
                    '=dst-address='.$destination,
                    '=gateway='.$gateway,
                    '=comment='.$comment,
                ]);
                $client->readResponse($connection);

                $syncResult = [
                    'routeros_route_id' => $existingRoute['.id'],
                    'operation' => 'updated',
                ];
            } else {
                $client->writeSentence($connection, [
                    '/ip/route/add',
                    '=dst-address='.$destination,
                    '=gateway='.$gateway,
                    '=comment='.$comment,
                ]);
                $client->readResponse($connection);

                $client->writeSentence($connection, [
                    '/ip/route/print',
                    '?comment='.$comment,
                ]);

                $createdRoute = collect($client->readResponse($connection))
                    ->first(fn (array $routerRoute): bool => ($routerRoute['comment'] ?? null) === $comment);

                $syncResult = [
                    'routeros_route_id' => $createdRoute['.id'] ?? null,
                    'operation' => 'created',
                ];
            }

            $route->forceFill([
                'routeros_route_id' => $syncResult['routeros_route_id'],
                'routeros_comment' => $comment,
                'routeros_sync_status' => 'synced',
                'routeros_sync_error' => null,
                'routeros_synced_at' => now(),
            ])->save();

            Log::info('Subscription IP route sync completed.', [
                'tenant_id' => $route->tenant_id,
                'subscription_id' => $route->subscription_id,
                'subscription_ip_route_id' => $route->id,
                'router_id' => $router->id,
                'destination' => $destination,
                'gateway' => $gateway,
                'routeros_route_id' => $syncResult['routeros_route_id'],
                'operation' => $syncResult['operation'],
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($subscription, $route, $router, $exception);
        }
    }

    private function markFailed(?Subscription $subscription, SubscriptionIpRoute $route, ?Router $router, Throwable $exception): void
    {
        Log::warning('Subscription IP route sync failed.', [
            'tenant_id' => $route->tenant_id,
            'subscription_id' => $route->subscription_id,
            'subscription_ip_route_id' => $route->id,
            'router_id' => $router?->id,
            'destination' => $route->destinationAddress(),
            'gateway' => $subscription?->ip_address,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);

        $route->forceFill([
            'routeros_sync_status' => 'failed',
            'routeros_sync_error' => 'RouterOS route sync failed: '.$exception->getMessage(),
        ])->save();

        $this->recordActivity($subscription, $route, 'ip_route_sync_failed', 'Subscription IP route sync failed');
    }

    private function recordActivity(?Subscription $subscription, SubscriptionIpRoute $route, string $event, string $message): void
    {
        if (! $subscription) {
            return;
        }

        activity()
            ->useLog('subscription')
            ->event($event)
            ->performedOn($subscription)
            ->causedBy(auth()->user())
            ->withProperties([
                'subscription_ip_route_id' => $route->id,
                'destination' => $route->destinationAddress(),
                'gateway' => $subscription->ip_address,
                'message' => $route->routeros_sync_error,
            ])
            ->log($message);
    }
}
