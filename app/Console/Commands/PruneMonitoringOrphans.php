<?php

namespace App\Console\Commands;

use App\Models\RouterMonitoringState;
use App\Models\SubscriptionBandwidthState;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monitoring:prune-orphans')]
#[Description('Remove monitoring state records whose router or subscription no longer exists')]
class PruneMonitoringOrphans extends Command
{
    public function handle(): int
    {
        $routerStates = RouterMonitoringState::withoutGlobalScopes()
            ->whereDoesntHave('router')
            ->delete();

        $subscriptionStates = SubscriptionBandwidthState::withoutGlobalScopes()
            ->whereDoesntHave('subscription')
            ->delete();

        $this->components->info("Monitoring orphan pruning completed. Router states: {$routerStates}, subscription states: {$subscriptionStates}.");

        return self::SUCCESS;
    }
}
