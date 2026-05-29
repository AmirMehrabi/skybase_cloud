<?php

namespace App\Services;

use App\Models\Subscription;
use App\Services\RouterOs\RouterOsClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubscriptionSessionDisconnectService
{
    public function __construct(
        private RouterOsClient $routerOs,
    ) {}

    public function disconnect(Subscription $subscription): SubscriptionSessionDisconnectResult
    {
        $subscription->loadMissing('router');
        $router = $subscription->router;
        $username = (string) $subscription->pppoe_username;

        if (! $subscription->isPppoe()) {
            return SubscriptionSessionDisconnectResult::skipped('Subscription is not a PPPoE service.');
        }

        if ($username === '') {
            return SubscriptionSessionDisconnectResult::skipped('Subscription has no PPPoE username.');
        }

        if (! $router) {
            return SubscriptionSessionDisconnectResult::skipped('Subscription has no assigned router.');
        }

        if (! $router->isMikrotik()) {
            return SubscriptionSessionDisconnectResult::skipped(
                'Assigned router does not support RouterOS API disconnects.',
                'routeros-api',
                $router->id,
                $router->name,
            );
        }

        if (! $router->enable_provisioning) {
            return SubscriptionSessionDisconnectResult::skipped(
                'Router provisioning is disabled.',
                'routeros-api',
                $router->id,
                $router->name,
            );
        }

        if (! $router->api_username || ! $router->api_password) {
            return SubscriptionSessionDisconnectResult::skipped(
                'RouterOS API credentials are missing.',
                'routeros-api',
                $router->id,
                $router->name,
            );
        }

        try {
            $removed = $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($username): int {
                $client->writeSentence($connection, [
                    '/ppp/active/print',
                    '?name='.$username,
                ]);

                $sessions = collect($client->readResponse($connection))
                    ->filter(fn (array $session): bool => ($session['name'] ?? null) === $username)
                    ->values();

                foreach ($sessions as $session) {
                    if (! isset($session['.id'])) {
                        continue;
                    }

                    $client->writeSentence($connection, [
                        '/ppp/active/remove',
                        '=.id='.$session['.id'],
                    ]);
                    $client->readResponse($connection);
                }

                return $sessions->filter(fn (array $session): bool => isset($session['.id']))->count();
            });

            return SubscriptionSessionDisconnectResult::success(
                $removed > 0
                    ? "Disconnected {$removed} active PPP session(s)."
                    : 'No active PPP session was found on the router.',
                'routeros-api',
                $router->id,
                $router->name,
                $removed,
            );
        } catch (Throwable $exception) {
            Log::warning('Suspended subscription router disconnect failed.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'subscription_code' => $subscription->subscription_code,
                'router_id' => $router->id,
                'router_name' => $router->name,
                'pppoe_username' => $username,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return SubscriptionSessionDisconnectResult::failed(
                'RouterOS API disconnect failed: '.$exception->getMessage(),
                'routeros-api',
                $router->id,
                $router->name,
            );
        }
    }

    public function recordActivity(Subscription $subscription, SubscriptionSessionDisconnectResult $result): void
    {
        $event = match ($result->status) {
            'success' => 'session_disconnect_succeeded',
            'skipped' => 'session_disconnect_skipped',
            default => 'session_disconnect_failed',
        };

        activity()
            ->useLog('subscription')
            ->event($event)
            ->performedOn($subscription)
            ->causedBy(auth()->user())
            ->withProperties($result->context())
            ->log(match ($result->status) {
                'success' => 'Suspended subscription session disconnect succeeded',
                'skipped' => 'Suspended subscription session disconnect skipped',
                default => 'Suspended subscription session disconnect failed',
            });
    }
}
