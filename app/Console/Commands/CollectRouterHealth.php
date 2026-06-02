<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Models\Tenant;
use App\Services\Monitoring\RouterHealthCollector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('monitoring:collect-router-health {--tenant= : Only collect routers for a tenant ID} {--router= : Only collect one router ID} {--force : Ignore the monitoring interval threshold}')]
#[Description('Collect router latency, packet loss, uptime, and resource samples')]
class CollectRouterHealth extends Command
{
    public function handle(RouterHealthCollector $collector): int
    {
        $checked = 0;
        $failed = 0;
        $threshold = now()->subSeconds((int) config('monitoring.step_seconds'));

        Tenant::query()
            ->where('status', 'active')
            ->when($this->option('tenant'), fn ($query, string $tenantId) => $query->where('id', $tenantId))
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($collector, $threshold, &$checked, &$failed): void {
                Router::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('enable_monitoring', true)
                    ->when($this->option('router'), fn ($query, string $routerId) => $query->whereKey($routerId))
                    ->when(! $this->option('force'), function ($query) use ($threshold): void {
                        $query->where(function ($query) use ($threshold): void {
                            $query->whereNull('last_status_checked_at')
                                ->orWhere('last_status_checked_at', '<=', $threshold);
                        });
                    })
                    ->orderBy('id')
                    ->chunkById(100, function ($routers) use ($collector, &$checked, &$failed): void {
                        foreach ($routers as $router) {
                            try {
                                $collector->collect($router);
                                $checked++;
                            } catch (Throwable $exception) {
                                $failed++;
                                $this->components->warn("Router {$router->id} failed: {$exception->getMessage()}");
                            }
                        }
                    }, 'id');
            });

        $this->components->info("Router health collection completed. Checked: {$checked}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
