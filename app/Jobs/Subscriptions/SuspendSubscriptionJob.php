<?php

namespace App\Jobs\Subscriptions;

use App\Models\Subscription;
use App\Models\User;
use App\Services\RadiusProvisioningService;
use App\Services\SubscriptionSessionDisconnectService;
use App\Services\TenantNotificationService;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SuspendSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public int $subscriptionId,
        public string $tenantId,
        public ?int $causedByUserId = null,
    ) {}

    public function handle(
        RadiusProvisioningService $radiusProvisioning,
        SubscriptionSessionDisconnectService $disconnectService,
        TenantNotificationService $notifications,
    ): void {
        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $this->tenantId)
            ->with(['customer', 'plan', 'router'])
            ->find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        $radiusProvisioning->syncSubscription($subscription);

        $disconnectResult = $disconnectService->disconnect($subscription);
        $disconnectService->recordActivity($subscription, $disconnectResult, $this->causer());

        $notifications->notifyAdmins($subscription->tenant_id, NotificationEventRegistry::SUBSCRIPTION_SUSPENDED, [
            'title' => 'Subscription suspended',
            'body' => "{$subscription->subscription_code} was suspended.",
            'action_url' => route('subscriptions.show', $subscription),
        ], $subscription);

        if ($subscription->customer) {
            $notifications->notifyCustomer($subscription->customer, NotificationEventRegistry::SUBSCRIPTION_SUSPENDED, [
                'title' => 'Your subscription was suspended',
                'body' => "{$subscription->subscription_code} is currently suspended.",
                'category' => 'service',
                'action_url' => route('customer.subscriptions.index'),
            ], $subscription);
        }

        if ($disconnectResult->shouldAlert()) {
            $notifications->notifyAdmins($subscription->tenant_id, NotificationEventRegistry::OPERATIONAL_FAILURE, [
                'title' => 'Router disconnect failed',
                'body' => $disconnectResult->message,
                'action_url' => route('subscriptions.show', $subscription),
            ], $subscription);
        }
    }

    private function causer(): ?User
    {
        if ($this->causedByUserId === null) {
            return null;
        }

        return User::query()
            ->where('tenant_id', $this->tenantId)
            ->find($this->causedByUserId);
    }
}
