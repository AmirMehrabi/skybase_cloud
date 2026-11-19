<?php

namespace App\Jobs\Subscriptions;

use App\Models\Subscription;
use App\Models\User;
use App\Services\SuspendedPppoeEnforcement;
use App\Services\TenantContext;
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
        SuspendedPppoeEnforcement $enforcement,
        TenantNotificationService $notifications,
    ): void {
        app(TenantContext::class)->run($this->tenantId, function () use ($enforcement, $notifications): void {
            $subscription = Subscription::withoutGlobalScopes()
                ->withoutTrashed()
                ->where('tenant_id', $this->tenantId)
                ->with(['customer', 'plan', 'router'])
                ->find($this->subscriptionId);

            if (! $subscription || $subscription->status !== 'suspended') {
                return;
            }

            $enforcement->enforce($subscription, causer: $this->causer());

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
        });
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
