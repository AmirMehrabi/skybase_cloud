<?php

namespace App\Jobs\Subscriptions;

use App\Models\Subscription;
use App\Services\RadiusProvisioningService;
use App\Services\TenantNotificationService;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ActivateSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public int $subscriptionId,
        public string $tenantId,
    ) {}

    public function handle(
        RadiusProvisioningService $radiusProvisioning,
        TenantNotificationService $notifications,
    ): void {
        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->with(['customer.organization', 'plan'])
            ->find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        $radiusProvisioning->syncSubscription($subscription);

        $notifications->notifyAdmins($subscription->tenant_id, NotificationEventRegistry::SUBSCRIPTION_ACTIVATED, [
            'title' => 'Subscription activated',
            'body' => "{$subscription->subscription_code} was activated.",
            'action_url' => route('subscriptions.show', $subscription),
        ], $subscription);

        if ($subscription->customer) {
            $notifications->notifyCustomer($subscription->customer, NotificationEventRegistry::SUBSCRIPTION_ACTIVATED, [
                'title' => 'Your subscription is active',
                'body' => "{$subscription->subscription_code} is now active.",
                'category' => 'service',
                'action_url' => route('customer.subscriptions.index'),
            ], $subscription);
        }
    }
}
