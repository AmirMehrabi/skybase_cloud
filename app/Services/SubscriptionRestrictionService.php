<?php

namespace App\Services;

use App\Jobs\Subscriptions\SuspendSubscriptionJob;
use App\Models\Subscription;
use App\Models\SubscriptionRestriction;

class SubscriptionRestrictionService
{
    public function __construct(
        private readonly RadiusProvisioningService $radiusProvisioning,
        private readonly SubscriptionSessionDisconnectService $disconnectService,
    ) {}

    public function restrict(Subscription $subscription, string $type, ?string $reason = null, array $metadata = []): bool
    {
        $restriction = SubscriptionRestriction::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'type' => $type,
                'cleared_at' => null,
            ],
            [
                'reason' => $reason,
                'metadata' => $metadata,
                'effective_at' => now(),
            ],
        );

        if ($type === 'billing' && config('skybase_billing.customer_full_auto_suspension') && $subscription->status === 'active') {
            $subscription->suspend();
            $restriction->update([
                'metadata' => [...($restriction->metadata ?? []), 'suspended_at' => $subscription->suspended_at->toDateTimeString()],
            ]);
            $subscription->unsetRelation('restrictions');
            $this->radiusProvisioning->syncSubscription($subscription);
            SuspendSubscriptionJob::dispatch($subscription->id, (string) $subscription->tenant_id)
                ->onQueue('subscriptions')->afterCommit();

            return true;
        }

        if (! $restriction->wasRecentlyCreated) {
            return false;
        }

        $subscription->unsetRelation('restrictions');
        $this->radiusProvisioning->syncSubscription($subscription);
        $this->disconnectService->disconnect($subscription);

        return true;
    }

    public function clear(Subscription $subscription, string $type, ?string $reason = null, ?int $userId = null): bool
    {
        $activeRestrictions = SubscriptionRestriction::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->where('type', $type)
            ->whereNull('cleared_at');

        $billingSuspendedAt = $type === 'billing' ? $activeRestrictions->first()?->metadata['suspended_at'] ?? null : null;
        $updated = $activeRestrictions->update([
            'cleared_at' => now(),
            'cleared_by' => $userId,
            'reason' => $reason,
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            return false;
        }

        if ($billingSuspendedAt !== null && $subscription->status === 'suspended' && $subscription->suspended_by === null
            && $subscription->suspended_at?->toDateTimeString() === $billingSuspendedAt) {
            $subscription->update(['status' => 'active', 'suspended_at' => null]);
        }

        $subscription->unsetRelation('restrictions');
        $this->radiusProvisioning->syncSubscription($subscription);

        return true;
    }
}
