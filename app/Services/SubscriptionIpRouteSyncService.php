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

        foreach ($subscription->ipRoutes as $route) {
            $this->syncRoute($route, $subscription);
        }
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
            $comment = $route->routerOsComment();
            $destination = $route->destinationAddress();
            $gateway = (string) $subscription->ip_address;

            $routeId = $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($comment, $destination, $gateway): ?string {
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

                    return $existingRoute['.id'];
                }

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

                return $createdRoute['.id'] ?? null;
            });

            $route->forceFill([
                'routeros_route_id' => $routeId,
                'routeros_comment' => $comment,
                'routeros_sync_status' => 'synced',
                'routeros_sync_error' => null,
                'routeros_synced_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
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

            $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($comment, $route): void {
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
            });
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
        $route->forceFill([
            'routeros_sync_status' => 'skipped',
            'routeros_sync_error' => $reason,
        ])->save();
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
