<?php

namespace App\Console\Commands;

use App\Models\SubscriptionUsageCycle;
use App\Models\Tenant;
use App\Services\SubscriptionUsageService;
use App\Services\TenantContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('usage:rollover')]
#[Description('Close expired subscription usage cycles and reset cycle data allowances')]
class RolloverSubscriptionUsageCycles extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SubscriptionUsageService $usage): int
    {
        $rolledOver = 0;
        Tenant::query()->each(function (Tenant $tenant) use ($usage, &$rolledOver): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant, $usage, &$rolledOver): void {
                SubscriptionUsageCycle::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('closed_at')
                    ->where('ends_at', '<', now())
                    ->with('subscription')
                    ->chunkById(100, function ($records) use ($usage, &$rolledOver): void {
                        foreach ($records as $record) {
                            $usage->rollover($record);
                            $rolledOver++;
                        }
                    });
            });
        });

        $this->info("Rolled over {$rolledOver} usage cycle(s).");

        return self::SUCCESS;
    }
}
