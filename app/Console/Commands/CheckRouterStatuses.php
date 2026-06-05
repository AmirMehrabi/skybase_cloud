<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\Tenant;
use App\Services\RouterStatusProbe;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('routers:check-status {--tenant= : Only check routers for a specific tenant ID} {--router= : Only check a specific router ID} {--force : Ignore the five-minute status check threshold}')]
#[Description('Check monitored router reachability and update online/offline status')]
class CheckRouterStatuses extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RouterStatusProbe $probe): int
    {
        $startedAt = now();
        $threshold = now()->subMinutes(5);
        $offlineFailureThreshold = max(1, (int) config('monitoring.router_status_offline_failure_threshold'));
        $checked = 0;
        $online = 0;
        $offline = 0;
        $changed = 0;
        $failed = 0;

        Log::info('Router status check started.', [
            'tenant_id' => $this->option('tenant'),
            'router_id' => $this->option('router'),
            'force' => (bool) $this->option('force'),
            'threshold' => $threshold->toIso8601String(),
        ]);

        $tenantQuery = Tenant::query()
            ->when($this->option('tenant'), fn ($query, string $tenantId) => $query->where('id', $tenantId))
            ->where('status', 'active');

        $tenantQuery->orderBy('id')->each(function (Tenant $tenant) use ($probe, $threshold, $offlineFailureThreshold, &$checked, &$online, &$offline, &$changed, &$failed): void {
            Log::info('Router status tenant scan started.', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);

            Router::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('enable_monitoring', true)
                ->when($this->option('router'), fn ($query, string $routerId) => $query->whereKey($routerId))
                ->when(! $this->option('force'), function ($query) use ($threshold): void {
                    $query->where(function ($query) use ($threshold): void {
                        $query->whereNull('last_status_checked_at')
                            ->orWhere('last_status_checked_at', '<=', $threshold);
                    });
                })
                ->chunkById(100, function ($routers) use ($probe, $offlineFailureThreshold, &$checked, &$online, &$offline, &$changed, &$failed): void {
                    foreach ($routers as $router) {
                        $previousStatus = $router->status ?? 'offline';

                        Log::info('Router status check started for router.', [
                            'tenant_id' => $router->tenant_id,
                            'router_id' => $router->id,
                            'router_name' => $router->name,
                            'ip_address' => $router->ip_address,
                            'previous_status' => $previousStatus,
                        ]);

                        try {
                            $result = $probe->check($router);
                            $failureCount = $result['online'] ? 0 : min(
                                $offlineFailureThreshold,
                                ((int) $router->status_check_failure_count) + 1,
                            );
                            $newStatus = $this->statusFromProbeResult($result['online'], $previousStatus, $failureCount, $offlineFailureThreshold);
                            $statusChanged = $previousStatus !== $newStatus;

                            $router->forceFill([
                                'status' => $newStatus,
                                'last_status_checked_at' => now(),
                                'last_status_changed_at' => $statusChanged ? now() : $router->last_status_changed_at,
                                'status_check_error' => $this->statusCheckError($result['error'], $result['online'], $failureCount, $offlineFailureThreshold),
                                'status_check_failure_count' => $failureCount,
                            ])->save();

                            $checked++;
                            $newStatus === 'online' ? $online++ : $offline++;

                            if ($statusChanged) {
                                $changed++;
                            }

                            Log::info('Router status check completed for router.', [
                                'tenant_id' => $router->tenant_id,
                                'router_id' => $router->id,
                                'router_name' => $router->name,
                                'endpoint' => $result['endpoint'],
                                'previous_status' => $previousStatus,
                                'new_status' => $newStatus,
                                'status_changed' => $statusChanged,
                                'latency_ms' => $result['latency_ms'],
                                'error' => $result['error'],
                                'method' => $result['method'] ?? null,
                                'failure_count' => $failureCount,
                            ]);
                        } catch (Throwable $exception) {
                            $failed++;
                            $failureCount = min($offlineFailureThreshold, ((int) $router->status_check_failure_count) + 1);
                            $newStatus = $this->statusFromProbeResult(false, $previousStatus, $failureCount, $offlineFailureThreshold);

                            $router->forceFill([
                                'status' => $newStatus,
                                'last_status_checked_at' => now(),
                                'last_status_changed_at' => $previousStatus !== $newStatus ? now() : $router->last_status_changed_at,
                                'status_check_error' => $this->statusCheckError($exception->getMessage(), false, $failureCount, $offlineFailureThreshold),
                                'status_check_failure_count' => $failureCount,
                            ])->save();

                            Log::error('Router status check failed for router.', [
                                'tenant_id' => $router->tenant_id,
                                'router_id' => $router->id,
                                'router_name' => $router->name,
                                'ip_address' => $router->ip_address,
                                'previous_status' => $previousStatus,
                                'new_status' => $newStatus,
                                'exception' => $exception::class,
                                'message' => $exception->getMessage(),
                                'failure_count' => $failureCount,
                            ]);
                        }
                    }
                }, 'id');

            Log::info('Router status tenant scan completed.', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);
        });

        Log::info('Router status check completed.', [
            'checked' => $checked,
            'online' => $online,
            'offline' => $offline,
            'changed' => $changed,
            'failed' => $failed,
            'duration_ms' => round($startedAt->diffInRealMilliseconds(now()), 2),
        ]);

        $this->components->info("Router status check completed. Checked: {$checked}, online: {$online}, offline: {$offline}, changed: {$changed}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function statusFromProbeResult(bool $online, string $previousStatus, int $failureCount, int $offlineFailureThreshold): string
    {
        if ($online) {
            return 'online';
        }

        if ($previousStatus === 'offline' || $failureCount >= $offlineFailureThreshold) {
            return 'offline';
        }

        return $previousStatus;
    }

    private function statusCheckError(?string $error, bool $online, int $failureCount, int $offlineFailureThreshold): ?string
    {
        if ($online) {
            return $error;
        }

        $message = $error ?: 'Router reachability check failed.';

        if ($failureCount < $offlineFailureThreshold) {
            return "{$message} Consecutive failure {$failureCount}/{$offlineFailureThreshold}; status unchanged.";
        }

        return $message;
    }
}
