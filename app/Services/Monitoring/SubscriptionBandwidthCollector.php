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
            if ($subscription->isPppoe() && filled($subscription->pppoe_username)) {
                // PPPoE: try RouterOS API first, then RADIUS accounting fallback
                if ($router && $router->isMikrotik() && $router->api_username && $router->api_password) {
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
                } else {
                    $fallback = $this->radiusAccountingDelta($subscription);
                    $sample['rx_bps'] = $fallback['rx_bps'];
                    $sample['tx_bps'] = $fallback['tx_bps'];
                    $sample['source'] = 'radius-accounting';
                    $sample['error'] = $fallback['error'];
                }
            } elseif (filled($subscription->pppoe_username)) {
                // Non-PPPoE with username (hotspot): use RADIUS accounting
                $fallback = $this->radiusAccountingDelta($subscription);
                $sample['rx_bps'] = $fallback['rx_bps'];
                $sample['tx_bps'] = $fallback['tx_bps'];
                $sample['source'] = 'radius-accounting';
                $sample['error'] = $fallback['error'];
            } else {
                throw new MonitoringStorageUnavailable('No username configured for bandwidth collection.');
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

        $username = $subscription->pppoe_username;

        $query = DB::table('radacct')->whereNull('acctstoptime');

        if (filled($username)) {
            $query->where('username', $username);
        } elseif (filled($subscription->mac_address)) {
            // For hotspot users, try matching by MAC address (calledstationid)
            $query->where('calledstationid', 'like', '%'.$subscription->mac_address.'%');
        } else {
            return ['rx_bps' => 0, 'tx_bps' => 0, 'error' => 'No username or MAC address configured for RADIUS lookup.'];
        }

        $session = $query->orderByDesc('acctupdatetime')->first();

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
