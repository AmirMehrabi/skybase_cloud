<?php

namespace App\Services;

use App\Models\Subscription;
use App\Services\RouterOs\RouterOsClient;
use App\Services\RouterOs\RouterOsCoaClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SubscriptionSessionDisconnectService
{
    public function __construct(
        private RouterOsClient $routerOs,
        private RouterOsCoaClient $coaClient,
    ) {}

    public function disconnect(Subscription $subscription): SubscriptionSessionDisconnectResult
    {
        return $this->disconnectForUsername($subscription, (string) $subscription->pppoe_username);
    }

    public function disconnectForUsername(Subscription $subscription, string $username): SubscriptionSessionDisconnectResult
    {
        $subscription->loadMissing('router');
        $router = $subscription->router;
        $radiusSession = $this->activeRadiusSession($subscription);

        if (! $subscription->isPppoe()) {
            return SubscriptionSessionDisconnectResult::skipped('Subscription is not a PPPoE service.');
        }

        if ($username === '') {
            return SubscriptionSessionDisconnectResult::skipped('Subscription has no PPPoE username.');
        }

        if (! $router) {
            return SubscriptionSessionDisconnectResult::skipped('Subscription has no assigned router.');
        }

        $apiResult = $this->disconnectViaRouterOsApi($subscription, $username);

        if ($apiResult->wasSuccessful()) {
            return $apiResult;
        }

        $coaResult = $this->disconnectViaCoa($subscription, $username, $radiusSession);

        if ($coaResult->wasSuccessful()) {
            $message = 'Disconnected 1 active PPP session(s) via CoA after RouterOS API: '.$apiResult->message;

            return SubscriptionSessionDisconnectResult::success(
                $message,
                'routeros-coa',
                $router->id,
                $router->name,
                $coaResult->sessionsRemoved,
            );
        }

        $message = $this->composeFailureMessage($apiResult, $coaResult);

        Log::warning('Subscription router disconnect failed.', [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'subscription_code' => $subscription->subscription_code,
            'router_id' => $router->id,
            'router_name' => $router->name,
            'pppoe_username' => $username,
            'radius_session' => $radiusSession ? [
                'acct_session_id' => $radiusSession->acctsessionid,
                'nas_ip_address' => $radiusSession->nasipaddress,
                'framed_ip_address' => $radiusSession->framedipaddress,
            ] : null,
            'api_result' => $apiResult->context(),
            'coa_result' => $coaResult->context(),
            'message' => $message,
        ]);

        return SubscriptionSessionDisconnectResult::failed(
            $message,
            $coaResult->method ?? $apiResult->method,
            $router->id,
            $router->name,
        );
    }

    private function disconnectViaRouterOsApi(Subscription $subscription, string $username): SubscriptionSessionDisconnectResult
    {
        $router = $subscription->router;

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
            return SubscriptionSessionDisconnectResult::failed(
                'RouterOS API credentials are missing.',
                'routeros-api',
                $router->id,
                $router->name,
            );
        }

        try {
            $removed = $this->routerOs->execute(
                $router,
                function ($connection, RouterOsClient $client) use ($username): int {
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
                },
                5,
            );

            if ($removed === 0) {
                return SubscriptionSessionDisconnectResult::skipped(
                    'No active PPP session was found on the router.',
                    'routeros-api',
                    $router->id,
                    $router->name,
                );
            }

            return SubscriptionSessionDisconnectResult::success(
                "Disconnected {$removed} active PPP session(s).",
                'routeros-api',
                $router->id,
                $router->name,
                $removed,
            );
        } catch (Throwable $exception) {
            return SubscriptionSessionDisconnectResult::failed(
                'RouterOS API disconnect failed: '.$exception->getMessage(),
                'routeros-api',
                $router->id,
                $router->name,
            );
        }
    }

    private function disconnectViaCoa(
        Subscription $subscription,
        string $username,
        ?object $radiusSession,
    ): SubscriptionSessionDisconnectResult {
        $router = $subscription->router;

        if (! $router) {
            return SubscriptionSessionDisconnectResult::skipped('Subscription has no assigned router.');
        }

        if (blank($router->coa_secret)) {
            return SubscriptionSessionDisconnectResult::failed(
                'RouterOS CoA secret is missing.',
                'routeros-coa',
                $router->id,
                $router->name,
            );
        }

        try {
            $removed = $this->coaClient->disconnect(
                $router,
                $username,
                $radiusSession?->acctsessionid,
                $radiusSession?->nasipaddress,
                $radiusSession?->framedipaddress ?? (filled($subscription->ip_address) ? (string) $subscription->ip_address : null),
                5,
            );

            return SubscriptionSessionDisconnectResult::success(
                'Disconnected 1 active PPP session(s) via CoA.',
                'routeros-coa',
                $router->id,
                $router->name,
                $removed,
            );
        } catch (Throwable $exception) {
            return SubscriptionSessionDisconnectResult::failed(
                'RouterOS CoA disconnect failed: '.$exception->getMessage(),
                'routeros-coa',
                $router->id,
                $router->name,
            );
        }
    }

    private function activeRadiusSession(Subscription $subscription): ?object
    {
        if (! Schema::hasTable('radacct')) {
            return null;
        }

        $username = (string) $subscription->pppoe_username;

        if ($username === '') {
            return null;
        }

        return DB::table('radacct')
            ->where('username', $username)
            ->whereNull('acctstoptime')
            ->orderByDesc('acctstarttime')
            ->first([
                'acctsessionid',
                'username',
                'nasipaddress',
                'framedipaddress',
            ]);
    }

    private function composeFailureMessage(
        SubscriptionSessionDisconnectResult $apiResult,
        SubscriptionSessionDisconnectResult $coaResult,
    ): string {
        return collect([$apiResult, $coaResult])
            ->filter(fn (SubscriptionSessionDisconnectResult $result): bool => $result->status !== 'success')
            ->map(fn (SubscriptionSessionDisconnectResult $result): string => $result->message)
            ->implode(' ');
    }
}
