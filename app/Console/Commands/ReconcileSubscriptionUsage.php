<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionUsageService;
use App\Services\TenantContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('usage:reconcile')]
#[Description('Reconcile RADIUS accounting usage and enforce subscription data limits')]
class ReconcileSubscriptionUsage extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SubscriptionUsageService $usage): int
    {
        $reconciled = 0;
        Tenant::query()->each(function (Tenant $tenant) use ($usage, &$reconciled): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant, $usage, &$reconciled): void {
                Subscription::withoutGlobalScopes()
                    ->withoutTrashed()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where('connection_type', 'pppoe')
                    ->whereNotNull('pppoe_username')
                    ->whereHas('plan', fn ($query) => $query->where('tenant_id', $tenant->id)->where('unlimited', false)->whereNotNull('data_limit'))
                    ->with('plan')
                    ->chunkById(100, function ($records) use ($usage, &$reconciled): void {
                        foreach ($records as $record) {
                            $usage->reconcile($record);
                            $reconciled++;
                        }
                    });
            });
        });

        $this->info("Reconciled {$reconciled} subscription(s).");

        return self::SUCCESS;
    }
}
