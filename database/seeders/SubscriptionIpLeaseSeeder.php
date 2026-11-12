<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Services\SubscriptionIpAllocationService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class SubscriptionIpLeaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app(TenantContext::class)->id();
        if ($tenantId === null || ! app()->environment('local', 'testing')) {
            return;
        }
        Subscription::query()->where('tenant_id', $tenantId)->where('status', 'active')->where('concurrent_devices', '>', 1)
            ->each(function (Subscription $subscription): void {
                if ($subscription->usesSessionIpAllocation()) {
                    app(SubscriptionIpAllocationService::class)->allocate($subscription, $subscription->router_id, 'seed:'.$subscription->id);
                }
            });
    }
}
