<?php

namespace App\Services\Monitoring;

use Symfony\Component\Process\Process;
use Throwable;

class PingProbe
{
    /**
     * @return array{online: bool, latency_ms: float|null, packet_loss_percent: float, error: string|null}
     */
    public function check(string $host, ?int $count = null, ?int $timeout = null): array
    {
        $count = max(1, $count ?? (int) config('monitoring.ping_count'));
        $timeout = max(1, $timeout ?? (int) config('monitoring.ping_timeout_seconds'));
        $process = new Process(['ping', '-c', (string) $count, '-W', (string) $timeout, $host], base_path(), null, null, ($count * $timeout) + 2);

        try {
            $process->run();
        } catch (Throwable $exception) {
            return [
                'online' => false,
                'latency_ms' => null,
                'packet_loss_percent' => 100.0,
                'error' => $exception->getMessage(),
            ];
        }

        $output = $process->getOutput()."\n".$process->getErrorOutput();
        $packetLoss = $this->packetLoss($output);
        $latency = $this->averageLatency($output);

        return [
            'online' => $packetLoss < 100 && $latency !== null,
            'latency_ms' => $latency,
            'packet_loss_percent' => $packetLoss,
            'error' => $process->isSuccessful() ? null : (trim($process->getErrorOutput()) ?: 'Ping failed.'),
        ];
    }

    private function packetLoss(string $output): float
    {
        if (preg_match('/(\d+(?:\.\d+)?)%\s+packet loss/', $output, $matches) === 1) {
            return (float) $matches[1];
        }

        return 100.0;
    }

    private function averageLatency(string $output): ?float
    {
        if (preg_match('/(?:rtt|round-trip).*=\s*([\d.]+)\/([\d.]+)\/([\d.]+)\/([\d.]+)/', $output, $matches) === 1) {
            return round((float) $matches[2], 2);
        }

        return null;
    }
}
