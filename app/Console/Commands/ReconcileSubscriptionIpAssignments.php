<?php

namespace App\Console\Commands;

use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Subscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ipam:reconcile-subscription-assignments {--tenant= : Restrict reconciliation to a tenant ID}')]
#[Description('Synchronize IPAM assignments from imported subscription IP addresses')]
class ReconcileSubscriptionIpAssignments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $assigned = 0;
        $conflicts = 0;
        $missing = 0;

        Subscription::withoutGlobalScopes()
            ->withoutTrashed()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$assigned, &$conflicts, &$missing): void {
                foreach ($subscriptions as $subscription) {
                    $ipAddress = $this->findIpAddress($subscription);

                    if (! $ipAddress) {
                        $missing++;
                        $this->components->warn("No IPAM address found for {$subscription->subscription_code} ({$subscription->ip_address}).");

                        continue;
                    }

                    if (in_array($ipAddress->status, ['reserved', 'blocked'], true)) {
                        $conflicts++;
                        $this->components->warn("{$ipAddress->ip_address} is {$ipAddress->status}; {$subscription->subscription_code} was not assigned.");

                        continue;
                    }

                    if ($ipAddress->status === 'assigned' && $ipAddress->subscription_code !== $subscription->subscription_code) {
                        $conflicts++;
                        $this->components->warn("{$ipAddress->ip_address} is already assigned to {$ipAddress->subscription_code}.");

                        continue;
                    }

                    $ipAddress->update([
                        'status' => 'assigned',
                        'customer_id' => $subscription->customer_id,
                        'mac_address' => $subscription->mac_address,
                        'subscription_code' => $subscription->subscription_code,
                        'assigned_at' => $ipAddress->assigned_at ?? $subscription->created_at ?? now(),
                    ]);
                    $assigned++;
                }
            });

        IpPool::withoutGlobalScopes()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->each->updateStatistics();

        $this->components->info("IPAM reconciliation complete. Assigned: {$assigned}; conflicts: {$conflicts}; not in a pool: {$missing}.");

        return self::SUCCESS;
    }

    private function findIpAddress(Subscription $subscription): ?IpAddress
    {
        $query = IpAddress::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('ip_address', $subscription->ip_address);

        if ($subscription->ip_pool_id) {
            return $query->where('ip_pool_id', $subscription->ip_pool_id)->first();
        }

        return $query->first();
    }
}
