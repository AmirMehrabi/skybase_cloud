<?php

namespace App\Console\Commands;

use App\Models\SubscriptionUsageCycle;
use App\Services\SubscriptionUsageService;
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
        SubscriptionUsageCycle::withoutGlobalScopes()
            ->whereNull('closed_at')
            ->where('ends_at', '<', now())
            ->with('subscription')
            ->chunkById(100, function ($cycles) use ($usage, &$rolledOver): void {
                foreach ($cycles as $cycle) {
                    $usage->rollover($cycle);
                    $rolledOver++;
                }
            });

        $this->info("Rolled over {$rolledOver} usage cycle(s).");

        return self::SUCCESS;
    }
}
