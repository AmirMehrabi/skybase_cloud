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

    /**
     * Render a PNG graph of subscription bandwidth using rrdtool.
     *
     * @return string|null Path to the rendered PNG file, or null if no data
     */
    public function renderSubscriptionBandwidthGraph(Subscription $subscription, string $range = '1h', int $width = 800, int $height = 300): ?string
    {
        $path = $this->subscriptionBandwidthPath($subscription);

        if (! File::exists($path)) {
            return null;
        }

        $tmpFile = sys_get_temp_dir().'/rrd_bw_'.$subscription->id.'_'.$range.'_'.$width.'x'.$height.'.png';
        $seconds = $this->rangeSeconds($range);

        $title = match ($range) {
            '1h' => 'Bandwidth - Last Hour',
            '6h' => 'Bandwidth - Last 6 Hours',
            '7d' => 'Bandwidth - Last 7 Days',
            '30d' => 'Bandwidth - Last 30 Days',
            default => 'Bandwidth - Last 24 Hours',
        };

        $vformat = match ($range) {
            '1h' => '%.1lf %Sbps',
            '6h' => '%.1lf %Sbps',
            default => '%.1lf %Sbps',
        };

        $command = [
            (string) config('monitoring.rrdtool'),
            'graph', $tmpFile,
            '--start', '-'.$seconds,
            '--end', 'now',
            '--width', (string) $width,
            '--height', (string) $height,
            '--title', $title,
            '--vertical-label', 'Bits per second',
            '--lower-limit', '0',
            '--rigid',
            '--imgformat', 'PNG',
            '--font', 'DEFAULT:10:',
            '--font', 'TITLE:13:',
            '--color', 'BACK#FFFFFF',
            '--color', 'CANVAS#FFFFFF',
            '--color', 'GRID#E5E7EB',
            '--color', 'MGRID#D1D5DB',
            '--color', 'SHADEA#FFFFFF',
            '--color', 'SHADEB#FFFFFF',
            "DEF:rx_avg={$path}:rx_bps:AVERAGE",
            "DEF:tx_avg={$path}:tx_bps:AVERAGE",
            "DEF:rx_max={$path}:rx_bps:MAX",
            "DEF:tx_max={$path}:tx_bps:MAX",
            'CDEF:rx_bits=rx_avg',
            'CDEF:tx_bits=tx_avg',
            'CDEF:rx_kbits=rx_avg,1000,/',
            'CDEF:tx_kbits=tx_avg,1000,/',
            'AREA:rx_avg#2563EB20:',
            'LINE1.5:rx_avg#2563EB:Download (RX)',
            'GPRINT:rx_avg:LAST:Current\: '.($vformat),
            'GPRINT:rx_avg:AVERAGE:Average\: '.($vformat),
            'GPRINT:rx_max:MAX:Max\: '.($vformat),
            'AREA:tx_avg#05966920:',
            'LINE1.5:tx_avg#059669:Upload (TX)',
            'GPRINT:tx_avg:LAST:Current\: '.($vformat),
            'GPRINT:tx_avg:AVERAGE:Average\: '.($vformat),
            'GPRINT:tx_max:MAX:Max\: '.($vformat),
        ];

        $this->run($command, throw: false);

        if (! File::exists($tmpFile)) {
            return null;
        }

        return $tmpFile;
    }

    /**
     * Return bandwidth series with richer data for chart rendering.
     *
     * @return array{chartData: list<array{timestamp: int, time: string, rx_bps: float|null, tx_bps: float|null, rx_max: float|null, tx_max: float|null}>, hasData: bool}
     */
    public function subscriptionBandwidthChartData(Subscription $subscription, string $range = '1h'): array
    {
        $path = $this->subscriptionBandwidthPath($subscription);

        if (! File::exists($path)) {
            return ['chartData' => [], 'hasData' => false];
        }

        $rows = $this->fetch($path, $range, ['rx_bps', 'tx_bps']);

        $chartData = array_map(function (array $row) use ($range): array {
            return [
                'timestamp' => $row['timestamp'],
                'time' => $this->formatTimestamp($row['timestamp'], $range),
                'rx_bps' => $row['rx_bps'],
                'tx_bps' => $row['tx_bps'],
            ];
        }, $rows);

        $hasData = false;
        foreach ($chartData as $row) {
            if (($row['rx_bps'] ?? 0) > 0 || ($row['tx_bps'] ?? 0) > 0) {
                $hasData = true;
                break;
            }
        }

        return ['chartData' => $chartData, 'hasData' => $hasData];
    }

    /**
     * Check if an RRD file exists for a subscription.
     */
    public function subscriptionBandwidthFileExists(Subscription $subscription): bool
    {
        return File::exists($this->subscriptionBandwidthPath($subscription));
    }

    private function formatTimestamp(int $timestamp, string $range): string
    {
        return match ($range) {
            '1h', '6h' => date('H:i', $timestamp),
            '7d', '30d' => date('M/d', $timestamp).' '.date('H:i', $timestamp),
            default => date('M/d H:i', $timestamp),
        };
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
        $directory = dirname($path);

        try {
            File::ensureDirectoryExists($directory);
        } catch (Throwable $exception) {
            throw new MonitoringStorageUnavailable("Unable to create monitoring RRD directory {$directory}: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_writable($directory)) {
            throw new MonitoringStorageUnavailable("Monitoring RRD directory is not writable: {$directory}");
        }
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
