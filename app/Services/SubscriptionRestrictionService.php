<?php

namespace App\Services;

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
        $updated = SubscriptionRestriction::withoutGlobalScopes()
            ->where('tenant_id', $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->where('type', $type)
            ->whereNull('cleared_at')
            ->update([
                'cleared_at' => now(),
                'cleared_by' => $userId,
                'reason' => $reason,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return false;
        }

        $subscription->unsetRelation('restrictions');
        $this->radiusProvisioning->syncSubscription($subscription);

        return true;
    }
}
