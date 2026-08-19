<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionUsageService;
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
        Subscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('connection_type', 'pppoe')
            ->whereNotNull('pppoe_username')
            ->whereHas('plan', fn ($query) => $query->where('unlimited', false)->whereNotNull('data_limit'))
            ->with('plan')
            ->chunkById(100, function ($subscriptions) use ($usage, &$reconciled): void {
                foreach ($subscriptions as $subscription) {
                    $usage->reconcile($subscription);
                    $reconciled++;
                }
            });

        $this->info("Reconciled {$reconciled} subscription(s).");

        return self::SUCCESS;
    }
}
