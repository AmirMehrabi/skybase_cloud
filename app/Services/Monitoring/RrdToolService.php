<?php

namespace App\Services\Monitoring;

use App\Models\Router;
use App\Models\Subscription;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Throwable;

class RrdToolService
{
    public function isAvailable(): bool
    {
        return $this->run([(string) config('monitoring.rrdtool'), '--version'], throw: false) !== null;
    }

    /**
     * @param  array{latency_ms: float|null, packet_loss_percent: float|null, online: bool, cpu_usage: int|null, memory_usage: int|null, active_sessions_count: int|null}  $sample
     */
    public function updateRouterHealth(Router $router, array $sample): void
    {
        $path = $this->routerHealthPath($router);

        $this->ensureRouterHealthArchive($path);
        $this->update($path, [
            $sample['latency_ms'],
            $sample['packet_loss_percent'],
            $sample['online'] ? 1 : 0,
            $sample['cpu_usage'],
            $sample['memory_usage'],
            $sample['active_sessions_count'],
        ]);
    }

    /**
     * @param  array{rx_bps: int|null, tx_bps: int|null}  $sample
     */
    public function updateSubscriptionBandwidth(Subscription $subscription, array $sample): void
    {
        $path = $this->subscriptionBandwidthPath($subscription);

        $this->ensureSubscriptionBandwidthArchive($path);
        $this->update($path, [
            $sample['rx_bps'],
            $sample['tx_bps'],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function routerHealthSeries(Router $router, string $range = '24h'): array
    {
        return $this->fetch($this->routerHealthPath($router), $range, [
            'latency_ms',
            'packet_loss_percent',
            'online',
            'cpu_usage',
            'memory_usage',
            'active_sessions_count' => 'sessions',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function subscriptionBandwidthSeries(Subscription $subscription, string $range = '1h'): array
    {
        return $this->fetch($this->subscriptionBandwidthPath($subscription), $range, [
            'rx_bps',
            'tx_bps',
        ]);
    }

    private function ensureRouterHealthArchive(string $path): void
    {
        if (File::exists($path)) {
            return;
        }

        $this->ensureDirectory($path);
        $step = $this->step();
        $heartbeat = $step * 2;

        $this->run([
            (string) config('monitoring.rrdtool'),
            'create',
            $path,
            '--step',
            (string) $step,
            "DS:latency_ms:GAUGE:{$heartbeat}:0:U",
            "DS:packet_loss_percent:GAUGE:{$heartbeat}:0:100",
            "DS:online:GAUGE:{$heartbeat}:0:1",
            "DS:cpu_usage:GAUGE:{$heartbeat}:0:100",
            "DS:memory_usage:GAUGE:{$heartbeat}:0:100",
            "DS:sessions:GAUGE:{$heartbeat}:0:U",
            'RRA:AVERAGE:0.5:1:1440',
            'RRA:AVERAGE:0.5:5:2016',
            'RRA:AVERAGE:0.5:60:2160',
            'RRA:MAX:0.5:5:2016',
            'RRA:MIN:0.5:5:2016',
        ]);
    }

    private function ensureSubscriptionBandwidthArchive(string $path): void
    {
        if (File::exists($path)) {
            return;
        }

        $this->ensureDirectory($path);
        $step = $this->step();
        $heartbeat = $step * 2;

        $this->run([
            (string) config('monitoring.rrdtool'),
            'create',
            $path,
            '--step',
            (string) $step,
            "DS:rx_bps:GAUGE:{$heartbeat}:0:U",
            "DS:tx_bps:GAUGE:{$heartbeat}:0:U",
            'RRA:AVERAGE:0.5:1:1440',
            'RRA:AVERAGE:0.5:5:2016',
            'RRA:AVERAGE:0.5:60:2160',
            'RRA:MAX:0.5:5:2016',
        ]);
    }

    /**
     * @param  array<int, float|int|null>  $values
     */
    private function update(string $path, array $values): void
    {
        $parts = array_map(fn (float|int|null $value): string => $value === null ? 'U' : (string) $value, $values);

        $this->run([
            (string) config('monitoring.rrdtool'),
            'update',
            $path,
            'N:'.implode(':', $parts),
        ]);
    }

    /**
     * @param  array<int|string, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $path, string $range, array $fields): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $output = $this->run([
            (string) config('monitoring.rrdtool'),
            'fetch',
            $path,
            'AVERAGE',
            '--start',
            '-'.$this->rangeSeconds($range),
            '--end',
            'now',
        ]);

        $rows = [];

        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$timestamp, $values] = array_map('trim', explode(':', $line, 2));

            if (! ctype_digit($timestamp)) {
                continue;
            }

            $columns = preg_split('/\s+/', trim($values)) ?: [];
            $row = ['timestamp' => (int) $timestamp];

            foreach (array_values($fields) as $index => $field) {
                $outputField = array_search($field, $fields, true);
                $outputField = is_string($outputField) ? $outputField : $field;
                $row[$outputField] = $this->rrdValue($columns[$index] ?? null);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function routerHealthPath(Router $router): string
    {
        return $this->tenantDirectory((string) $router->tenant_id)."/router-{$router->id}-health.rrd";
    }

    private function subscriptionBandwidthPath(Subscription $subscription): string
    {
        return $this->tenantDirectory((string) $subscription->tenant_id)."/subscription-{$subscription->id}-bandwidth.rrd";
    }

    private function tenantDirectory(string $tenantId): string
    {
        return rtrim((string) config('monitoring.rrd_root'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$tenantId;
    }

    private function ensureDirectory(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
    }

    private function step(): int
    {
        return max(10, (int) config('monitoring.step_seconds'));
    }

    private function rangeSeconds(string $range): int
    {
        return match ($range) {
            '1h' => 3600,
            '6h' => 21600,
            '7d' => 604800,
            '30d' => 2592000,
            default => 86400,
        };
    }

    private function rrdValue(?string $value): ?float
    {
        if ($value === null || strtolower($value) === 'nan') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<int, string>  $command
     */
    private function run(array $command, bool $throw = true): ?string
    {
        $process = new Process($command, base_path(), null, null, 30);

        try {
            $process->run();
        } catch (Throwable $exception) {
            if (! $throw) {
                return null;
            }

            throw new MonitoringStorageUnavailable($exception->getMessage(), previous: $exception);
        }

        if ($process->isSuccessful()) {
            return $process->getOutput();
        }

        if (! $throw) {
            return null;
        }

        $message = trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'RRDTool command failed.';

        throw new MonitoringStorageUnavailable($message);
    }
}
