<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Monitoring\SubscriptionBandwidthCollector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('monitoring:collect-subscription-bandwidth {--tenant= : Only collect subscriptions for a tenant ID} {--subscription= : Only collect one subscription ID}')]
#[Description('Collect live bandwidth samples for active MikroTik PPPoE subscriptions')]
class CollectSubscriptionBandwidth extends Command
{
    public function handle(SubscriptionBandwidthCollector $collector): int
    {
        $checked = 0;
        $failed = 0;

        Tenant::query()
            ->where('status', 'active')
            ->when($this->option('tenant'), fn ($query, string $tenantId) => $query->where('id', $tenantId))
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($collector, &$checked, &$failed): void {
                Subscription::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where('connection_type', 'pppoe')
                    ->whereNotNull('pppoe_username')
                    ->when($this->option('subscription'), fn ($query, string $subscriptionId) => $query->whereKey($subscriptionId))
                    ->with('router')
                    ->orderBy('id')
                    ->chunkById(100, function ($subscriptions) use ($collector, &$checked, &$failed): void {
                        foreach ($subscriptions as $subscription) {
                            try {
                                $collector->collect($subscription);
                                $checked++;
                            } catch (Throwable $exception) {
                                $failed++;
                                $this->components->warn("Subscription {$subscription->id} failed: {$exception->getMessage()}");
                            }
                        }
                    }, 'id');
            });

        $this->components->info("Subscription bandwidth collection completed. Checked: {$checked}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
