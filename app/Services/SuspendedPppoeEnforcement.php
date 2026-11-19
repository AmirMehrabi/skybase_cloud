<?php

namespace App\Services;

use App\Models\RadiusCheck;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\User;
use App\Services\RouterOs\RouterOsClient;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class SuspendedPppoeEnforcement
{
    public function __construct(
        private RouterOsClient $routerOs,
        private SubscriptionSessionDisconnectService $disconnects,
        private RadiusProvisioningService $radiusProvisioning,
        private OperationalIncidentNotifier $incidents,
    ) {}

    /**
     * @return list<string>
     */
    public function activeUsernames(Router $router, ?string $username = null): array
    {
        if (! $router->isMikrotik() || ! $router->api_username || ! $router->api_password) {
            throw new RuntimeException('RouterOS API credentials are missing or the router does not support RouterOS.');
        }

        return $this->routerOs->execute($router, function ($connection, RouterOsClient $client) use ($username): array {
            $client->writeSentence($connection, [
                '/ppp/active/print',
                '=.proplist=name',
                ...($username === null ? [] : ['?name='.$username]),
            ]);

            return array_column($client->readResponse($connection), 'name');
        }, 5);
    }

    public function enforce(Subscription $candidate, ?bool $observedActive = null, ?User $causer = null): SubscriptionSessionDisconnectResult
    {
        return Cache::lock("suspended-pppoe:{$candidate->tenant_id}:{$candidate->id}", 45)
            ->get(fn () => app(TenantContext::class)->run((string) $candidate->tenant_id, function () use ($candidate, $observedActive, $causer): SubscriptionSessionDisconnectResult {
                $subscription = Subscription::query()->where('tenant_id', $candidate->tenant_id)
                    ->where('status', 'suspended')->with('router')->find($candidate->id);

                if (! $subscription || ! $subscription->isPppoe() || blank($subscription->pppoe_username)) {
                    return SubscriptionSessionDisconnectResult::skipped('Subscription no longer requires PPPoE suspension enforcement.');
                }

                if ($this->radiusProvisioning->sharesPppoeUsernameWithActiveSubscription($subscription)) {
                    $message = 'The PPPoE username is also assigned to an active subscription. Assign distinct usernames before enforcing suspension.';
                    $this->incidents->observe(
                        $subscription,
                        'suspended-pppoe',
                        false,
                        'Suspended subscription shares an active PPPoE username',
                        "{$subscription->subscription_code}. {$message}",
                        route('subscriptions.show', $subscription),
                    );

                    return SubscriptionSessionDisconnectResult::failed($message);
                }

                if ($candidate->router_id !== $subscription->router_id || $candidate->pppoe_username !== $subscription->pppoe_username) {
                    $observedActive = null;
                }

                try {
                    $this->restoreRadiusReject($subscription);
                } catch (Throwable $exception) {
                    $this->incidents->observe(
                        $subscription,
                        'suspended-pppoe',
                        false,
                        'Suspended PPPoE login block could not be verified',
                        "{$subscription->subscription_code}. Check RADIUS provisioning and database access.",
                        route('subscriptions.show', $subscription),
                    );
                    throw $exception;
                }

                return $this->enforceCurrent($subscription, $observedActive, $causer);
            })) ?: SubscriptionSessionDisconnectResult::skipped('Suspension enforcement is already running.');
    }

    private function restoreRadiusReject(Subscription $subscription): void
    {
        $subscription->getConnection()->transaction(function () use ($subscription): void {
            $current = Subscription::query()->where('tenant_id', $subscription->tenant_id)
                ->where('status', 'suspended')->lockForUpdate()->find($subscription->id);

            if ($current && ! RadiusCheck::query()->where('tenant_id', $current->tenant_id)
                ->where('username', $current->pppoe_username)->where('attribute', 'Auth-Type')
                ->where('op', ':=')->where('value', 'Reject')->exists()) {
                $this->radiusProvisioning->syncSubscription($current);
            }
        });
    }

    private function enforceCurrent(Subscription $subscription, ?bool $active, ?User $causer): SubscriptionSessionDisconnectResult
    {
        $router = $subscription->router;
        $username = (string) $subscription->pppoe_username;
        $result = null;
        $verificationError = null;

        try {
            $active ??= $router ? in_array($username, $this->activeUsernames($router, $username), true) : null;
        } catch (Throwable) {
            // The disconnect fallbacks can still work when an API read fails.
        }

        $currentSubscription = Subscription::query()->where('tenant_id', $subscription->tenant_id)
            ->whereKey($subscription->id)->where('status', 'suspended')
            ->where('router_id', $subscription->router_id)->where('pppoe_username', $username);

        if ($active !== false) {
            $result = $subscription->getConnection()->transaction(function () use ($currentSubscription, $subscription): ?SubscriptionSessionDisconnectResult {
                if (! (clone $currentSubscription)->lockForUpdate()->first(['id'])) {
                    return null;
                }

                return $this->disconnects->disconnectSuspended($subscription);
            });

            if ($result === null) {
                return SubscriptionSessionDisconnectResult::skipped('Subscription changed before disconnect.');
            }

            try {
                $active = $router ? in_array($username, $this->activeUsernames($router, $username), true) : null;
            } catch (Throwable $exception) {
                $active = null;
                $verificationError = $exception->getMessage();
            }
        }

        $reason = $result && ! $result->wasSuccessful() ? ' '.$result->message : '';
        $outcome = match ($active) {
            false => SubscriptionSessionDisconnectResult::success('No active PPP session remains on the assigned router.', $result?->method ?? 'routeros-api', $router?->id, $router?->name, $result?->sessionsRemoved ?? 0),
            true => SubscriptionSessionDisconnectResult::failed('A suspended PPP session is still active.'.$reason, $result?->method, $router?->id, $router?->name),
            null => SubscriptionSessionDisconnectResult::failed('Unable to verify PPP session state.'.($verificationError ? ' '.$verificationError : '').$reason, $result?->method, $router?->id, $router?->name),
        };

        if (! $currentSubscription->exists()) {
            return SubscriptionSessionDisconnectResult::skipped('Subscription changed during suspension enforcement.');
        }

        $currentSubscription->update([
            'connection_status' => match ($active) {
                true => 'online',
                false => 'offline',
                null => null,
            },
            'connection_status_checked_at' => now(),
        ]);

        if ($result !== null || $subscription->connection_status !== 'offline') {
            $this->disconnects->recordActivity($subscription, $outcome, $causer);
        }

        $this->incidents->observe(
            $subscription,
            'suspended-pppoe',
            $active === false,
            match ($active) {
                false => 'Suspended PPPoE session cleared',
                true => 'Suspended PPPoE session is still active',
                null => 'Suspended PPPoE session could not be verified',
            },
            "{$subscription->subscription_code} ({$username}) on ".($router?->name ?? 'an unassigned router').'. '.$outcome->message,
            route('subscriptions.show', $subscription),
        );

        return $outcome;
    }
}
