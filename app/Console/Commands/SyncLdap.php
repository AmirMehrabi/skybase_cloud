<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Ldap\LdapSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class SyncLdap extends Command
{
    protected $signature = 'ldap:sync {tenant? : Tenant ID to sync} {--all : Sync all LDAP-enabled tenants} {--dry-run : Preview changes without writing}';

    protected $description = 'Synchronize LDAP customers and subscriptions into SkyBase.';

    public function handle(LdapSyncService $sync): int
    {
        $tenantId = $this->argument('tenant');
        $dryRun = (bool) $this->option('dry-run');

        $tenants = $tenantId
            ? Tenant::query()->whereKey($tenantId)->get()
            : Tenant::query()->get();

        if ($tenantId && $tenants->isEmpty()) {
            $this->error("Tenant [{$tenantId}] was not found.");

            return self::FAILURE;
        }

        $exitCode = self::SUCCESS;

        foreach ($tenants as $tenant) {
            $settings = $sync->settingsForTenant($tenant->id);

            if (! (bool) $settings['connection']['enabled']) {
                if ($tenantId || $this->option('all')) {
                    $this->line("Skipping {$tenant->id}: LDAP sync is disabled.");
                }

                continue;
            }

            if (! $tenantId && ! $dryRun && ! $this->isDue($settings)) {
                continue;
            }

            try {
                $result = $sync->syncTenant($tenant, $dryRun);
                $this->info($this->message($tenant->id, $result));
            } catch (Throwable $exception) {
                $exitCode = self::FAILURE;
                $this->error("LDAP sync failed for {$tenant->id}: {$exception->getMessage()}");
            }
        }

        return $exitCode;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function message(string $tenantId, array $result): string
    {
        return sprintf(
            'Synced %s: customers %d created / %d updated / %d skipped / %d missing; subscriptions %d created / %d updated / %d skipped / %d missing.',
            $tenantId,
            $result['customers']['created'],
            $result['customers']['updated'],
            $result['customers']['skipped'],
            $result['customers']['missing'],
            $result['subscriptions']['created'],
            $result['subscriptions']['updated'],
            $result['subscriptions']['skipped'],
            $result['subscriptions']['missing'],
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function isDue(array $settings): bool
    {
        $lastRunAt = $settings['sync_status']['last_run_at'] ?? null;

        if (! filled($lastRunAt)) {
            return true;
        }

        $interval = (int) ($settings['connection']['sync_interval_minutes'] ?? 15);

        return CarbonImmutable::parse($lastRunAt)->addMinutes($interval)->isPast();
    }
}
