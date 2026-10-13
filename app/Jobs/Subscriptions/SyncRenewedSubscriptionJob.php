<?php

namespace App\Jobs\Subscriptions;

use App\Models\Subscription;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionIpRouteSyncService;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncRenewedSubscriptionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $subscriptionId, public string $tenantId) {}

    public function handle(RadiusProvisioningService $radius, SubscriptionIpRouteSyncService $routes): void
    {
        app(TenantContext::class)->run($this->tenantId, function () use ($radius, $routes): void {
            $subscription = Subscription::withoutGlobalScopes()->withoutTrashed()->where('tenant_id', $this->tenantId)
                ->with(['customer.organization', 'plan', 'restrictions'])->find($this->subscriptionId);

            if (! $subscription) {
                return;
            }

            $radius->syncSubscription($subscription);
            $routes->syncRoutes($subscription);

        });
    }
}
