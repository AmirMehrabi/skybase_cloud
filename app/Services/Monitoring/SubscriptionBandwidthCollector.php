<?php

namespace App\Services\Monitoring;

use App\Models\Subscription;
use App\Models\SubscriptionBandwidthState;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SubscriptionBandwidthCollector
{
    public function __construct(
        private RouterOsMonitoringService $routerOsMonitoring,
        private RrdToolService $rrdTool,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function collect(Subscription $subscription): array
    {
        $subscription->loadMissing('router');
        $sampledAt = now();
        $router = $subscription->router;
        $sample = [
            'tenant_id' => (string) $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'router_id' => $router?->id,
            'interface_name' => null,
            'rx_bps' => 0,
            'tx_bps' => 0,
            'source' => 'routeros',
            'sampled_at' => $sampledAt,
            'error' => null,
        ];

        try {
            if (! $subscription->isPppoe() || blank($subscription->pppoe_username)) {
                throw new MonitoringStorageUnavailable('Live bandwidth is available for PPPoE subscriptions with a username.');
            }

            if (! $router || ! $router->isMikrotik() || ! $router->api_username || ! $router->api_password) {
                throw new MonitoringStorageUnavailable('A MikroTik router with API credentials is required.');
            }

            $interface = $this->routerOsMonitoring->activePppInterface($router, (string) $subscription->pppoe_username);

            if ($interface) {
                $traffic = $this->routerOsMonitoring->interfaceTraffic($router, $interface);
                $sample['interface_name'] = $interface;
                $sample['rx_bps'] = $traffic['rx_bps'];
                $sample['tx_bps'] = $traffic['tx_bps'];
                $sample['source'] = $traffic['source'];
            } else {
                $fallback = $this->radiusAccountingDelta($subscription);
                $sample['rx_bps'] = $fallback['rx_bps'];
                $sample['tx_bps'] = $fallback['tx_bps'];
                $sample['source'] = 'radius-accounting';
                $sample['error'] = $fallback['error'];
            }

            $this->rrdTool->updateSubscriptionBandwidth($subscription, [
                'rx_bps' => $sample['rx_bps'],
                'tx_bps' => $sample['tx_bps'],
            ]);
        } catch (Throwable $exception) {
            $sample['error'] = $exception->getMessage();
        }

        SubscriptionBandwidthState::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id],
            $sample,
        );

        return $sample;
    }

    /**
     * @return array{rx_bps: int, tx_bps: int, error: string|null}
     */
    private function radiusAccountingDelta(Subscription $subscription): array
    {
        if (! Schema::hasTable('radacct')) {
            return ['rx_bps' => 0, 'tx_bps' => 0, 'error' => 'No active RouterOS PPP session was found and radacct is unavailable.'];
        }

        $session = DB::table('radacct')
            ->where('username', $subscription->pppoe_username)
            ->whereNull('acctstoptime')
            ->orderByDesc('acctupdatetime')
            ->first();

        if (! $session) {
            return ['rx_bps' => 0, 'tx_bps' => 0, 'error' => 'No active PPP or RADIUS accounting session was found.'];
        }

        $downloadBytes = $this->octets($session, 'acctoutputoctets', 'acctoutputgigawords');
        $uploadBytes = $this->octets($session, 'acctinputoctets', 'acctinputgigawords');
        $cacheKey = "monitoring:subscription-bandwidth:{$subscription->tenant_id}:{$subscription->id}";
        $previous = Cache::get($cacheKey);

        Cache::put($cacheKey, [
            'download' => $downloadBytes,
            'upload' => $uploadBytes,
            'sampled_at' => now()->timestamp,
        ], now()->addMinutes(10));

        if (! is_array($previous) || ! isset($previous['sampled_at'])) {
            return ['rx_bps' => 0, 'tx_bps' => 0, 'error' => 'Waiting for the next accounting sample to calculate live bandwidth.'];
        }

        $seconds = max(1, now()->timestamp - (int) $previous['sampled_at']);

        return [
            'rx_bps' => max(0, (int) round((($downloadBytes - (int) ($previous['download'] ?? 0)) * 8) / $seconds)),
            'tx_bps' => max(0, (int) round((($uploadBytes - (int) ($previous['upload'] ?? 0)) * 8) / $seconds)),
            'error' => null,
        ];
    }

    private function octets(object $session, string $octetsColumn, string $gigawordsColumn): int
    {
        return (int) ($session->{$octetsColumn} ?? 0) + ((int) ($session->{$gigawordsColumn} ?? 0) * 4294967296);
    }
}
