<?php

namespace App\Services\Monitoring;

use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomerBandwidthUsageService
{
    public function __construct(private RrdToolService $rrdTool) {}

    /**
     * @param  Collection<int, Subscription>  $subscriptions
     * @return array{chartData: list<array{timestamp: int, time: string, rx_bps: float|null, tx_bps: float|null, total_bps: float|null}>, hasData: bool}
     */
    public function aggregate(Collection $subscriptions, string $range): array
    {
        $seriesByTimestamp = [];

        foreach ($subscriptions as $subscription) {
            try {
                $chartData = $this->rrdTool->subscriptionBandwidthChartData($subscription, $range)['chartData'];
            } catch (\Throwable $exception) {
                Log::warning('Customer subscription bandwidth history could not be aggregated.', [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'range' => $range,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            foreach ($chartData as $point) {
                $timestamp = (int) $point['timestamp'];
                $aggregate = $seriesByTimestamp[$timestamp] ?? [
                    'timestamp' => $timestamp,
                    'time' => (string) $point['time'],
                    'rx_bps' => null,
                    'tx_bps' => null,
                ];

                if ($point['rx_bps'] !== null) {
                    $aggregate['rx_bps'] = (float) ($aggregate['rx_bps'] ?? 0) + (float) $point['rx_bps'];
                }

                if ($point['tx_bps'] !== null) {
                    $aggregate['tx_bps'] = (float) ($aggregate['tx_bps'] ?? 0) + (float) $point['tx_bps'];
                }

                $seriesByTimestamp[$timestamp] = $aggregate;
            }
        }

        ksort($seriesByTimestamp);

        $chartData = array_values(array_map(function (array $point): array {
            $point['total_bps'] = $point['rx_bps'] === null && $point['tx_bps'] === null
                ? null
                : (float) ($point['rx_bps'] ?? 0) + (float) ($point['tx_bps'] ?? 0);

            return $point;
        }, $seriesByTimestamp));

        return [
            'chartData' => $chartData,
            'hasData' => collect($chartData)->contains(
                fn (array $point): bool => ($point['rx_bps'] ?? 0) > 0 || ($point['tx_bps'] ?? 0) > 0
            ),
        ];
    }
}
