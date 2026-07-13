<?php

namespace App\Services\Monitoring;

use App\Models\Subscription;
use App\Models\SubscriptionBandwidthState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public function collect(Subscription $subscription, ?int $timeoutSeconds = null): array
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
                    try {
                        $interface = $this->routerOsMonitoring->activePppInterface($router, (string) $subscription->pppoe_username, $timeoutSeconds);

                        if (! $interface) {
                            throw new MonitoringStorageUnavailable('No active RouterOS PPP session was found.');
                        }

                        $traffic = $this->routerOsMonitoring->interfaceTraffic($router, $interface, $timeoutSeconds);
                        $sample['interface_name'] = $interface;
                        $sample['rx_bps'] = $traffic['rx_bps'];
                        $sample['tx_bps'] = $traffic['tx_bps'];
                        $sample['source'] = $traffic['source'];
                    } catch (Throwable) {
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

            if ($sample['error'] === null) {
                $this->rrdTool->updateSubscriptionBandwidth($subscription, [
                    'rx_bps' => $sample['rx_bps'],
                    'tx_bps' => $sample['tx_bps'],
                ]);
            }
        } catch (Throwable $exception) {
            $sample['error'] = $exception->getMessage();
        }

        $existingState = SubscriptionBandwidthState::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->first();
        $sample['last_success_at'] = $sample['error'] === null ? $sampledAt : $existingState?->last_success_at;
        $sample['consecutive_failures'] = $sample['error'] === null ? 0 : ((int) $existingState?->consecutive_failures + 1);

        SubscriptionBandwidthState::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id],
            $sample,
        );

        if ($sample['error'] !== null) {
            Log::warning('Subscription bandwidth collection failed.', [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'router_id' => $router?->id,
                'source' => $sample['source'],
                'error' => $sample['error'],
            ]);
        }

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
        $sampledAt = now();
        $state = SubscriptionBandwidthState::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->first();

        SubscriptionBandwidthState::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $subscription->tenant_id, 'subscription_id' => $subscription->id],
            [
                'router_id' => $subscription->router_id,
                'last_download_bytes' => $downloadBytes,
                'last_upload_bytes' => $uploadBytes,
                'counter_sampled_at' => $sampledAt,
            ],
        );

        if (! $state?->counter_sampled_at || $state->last_download_bytes === null || $state->last_upload_bytes === null) {
            return ['rx_bps' => 0, 'tx_bps' => 0, 'error' => 'Waiting for the next accounting sample to calculate live bandwidth.'];
        }

        if ($downloadBytes < $state->last_download_bytes || $uploadBytes < $state->last_upload_bytes) {
            return ['rx_bps' => 0, 'tx_bps' => 0, 'error' => 'Accounting counters restarted; waiting for the next sample.'];
        }

        $seconds = max(1, $state->counter_sampled_at->diffInSeconds($sampledAt));

        return [
            'rx_bps' => (int) round((($downloadBytes - $state->last_download_bytes) * 8) / $seconds),
            'tx_bps' => (int) round((($uploadBytes - $state->last_upload_bytes) * 8) / $seconds),
            'error' => null,
        ];
    }

    private function octets(object $session, string $octetsColumn, string $gigawordsColumn): int
    {
        return (int) ($session->{$octetsColumn} ?? 0) + ((int) ($session->{$gigawordsColumn} ?? 0) * 4294967296);
    }
}
