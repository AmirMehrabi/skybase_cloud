<?php

namespace App\Services\TrafficShaping;

use App\Models\Plan;

class PlanTrafficShapingService
{
    public function mikrotikRateLimit(?Plan $plan): ?string
    {
        if (! $plan || $plan->shaping_mode === 'disabled') {
            return null;
        }

        if (! $plan->upload_speed || ! $plan->download_speed) {
            return null;
        }

        if (! $plan->usesAdvancedShaping()) {
            return $this->ratePair((int) $plan->upload_speed, (int) $plan->download_speed, (string) $plan->bandwidth_unit);
        }

        $parts = [
            $this->ratePair((int) $plan->upload_speed, (int) $plan->download_speed, (string) $plan->bandwidth_unit),
        ];

        $parts[] = $this->ratePair(
            (int) ($plan->burst_upload ?: $plan->upload_speed),
            (int) ($plan->burst_download ?: $plan->download_speed),
            (string) $plan->bandwidth_unit,
        );
        $parts[] = $this->ratePair(
            (int) ($plan->burst_threshold_upload ?: $plan->upload_speed),
            (int) ($plan->burst_threshold_download ?: $plan->download_speed),
            (string) $plan->bandwidth_unit,
        );
        $parts[] = $this->timePair(
            (int) ($plan->burst_time_upload ?: 1),
            (int) ($plan->burst_time_download ?: 1),
        );
        $parts[] = (string) ($plan->shaping_priority ?: 8);
        $parts[] = $this->ratePair(
            (int) ($plan->min_upload_speed ?? $plan->upload_speed),
            (int) ($plan->min_download_speed ?? $plan->download_speed),
            (string) $plan->bandwidth_unit,
        );

        return implode(' ', $parts);
    }

    public function summary(?Plan $plan): string
    {
        if (! $plan || $plan->shaping_mode === 'disabled') {
            return 'Traffic shaping disabled';
        }

        $rateLimit = $this->mikrotikRateLimit($plan);

        if ($rateLimit === null) {
            return 'No rate limit configured';
        }

        return $rateLimit;
    }

    protected function ratePair(int $upload, int $download, string $unit): string
    {
        return $this->rate($upload, $unit).'/'.$this->rate($download, $unit);
    }

    protected function timePair(int $upload, int $download): string
    {
        return max(1, $upload).'/'.max(1, $download);
    }

    protected function rate(int $value, string $unit): string
    {
        $value = max(0, $value);

        return match (strtolower($unit)) {
            'kbps', 'kbit', 'kbits' => $value.'k',
            'gbps', 'gbit', 'gbits' => ($value * 1000).'M',
            default => $value.'M',
        };
    }
}
