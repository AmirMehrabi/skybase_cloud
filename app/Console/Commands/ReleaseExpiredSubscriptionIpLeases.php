<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\SubscriptionIpAllocationService;
use App\Services\TenantContext;
use Illuminate\Console\Command;

class ReleaseExpiredSubscriptionIpLeases extends Command
{
    protected $signature = 'subscriptions:release-expired-ip-leases';

    protected $description = 'Release expired pending IP allocations without reclaiming confirmed sessions';

    public function handle(SubscriptionIpAllocationService $allocator, TenantContext $context): int
    {
        Tenant::query()->each(function (Tenant $tenant) use ($allocator, $context): void {
            $context->run((string) $tenant->id, function () use ($tenant, $allocator): void {
                Subscription::query()->withTrashed()->where('tenant_id', $tenant->id)
                    ->whereHas('ipLeases', fn ($query) => $query->whereNull('released_at')->whereNull('confirmed_at')->where('pending_until', '<', now()))
                    ->each(function (Subscription $subscription) use ($allocator): void {
                        foreach ($subscription->ipLeases()->whereNull('released_at')->whereNull('confirmed_at')->where('pending_until', '<', now())->get() as $lease) {
                            $allocator->release($subscription, $lease->token, onlyExpiredPending: true);
                        }
                    });
            });
        });

        return self::SUCCESS;
    }
}
