<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationBillingService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function applyDefaultsToSubscriptionAttributes(array $attributes): array
    {
        $organization = $this->organizationForCustomerId((int) $attributes['customer_id']);

        if (! $organization?->billing_enabled) {
            return $attributes;
        }

        $this->ensurePlanMatches($organization, (int) ($attributes['plan_id'] ?? 0));

        $attributes['plan_id'] = $organization->default_plan_id;
        $attributes['billing_cycle'] = $organization->default_billing_cycle;
        $attributes['grace_period_days'] = $organization->default_grace_period_days;
        $attributes['billing_enabled'] = true;
        $attributes['billing_disabled_at'] = null;

        if ($organization->defaultPlan) {
            $attributes['base_price'] = $organization->defaultPlan->price;
        }

        return $attributes;
    }

    public function syncOrganizationSubscriptions(Organization $organization): void
    {
        if (! $organization->billing_enabled) {
            return;
        }

        DB::transaction(function () use ($organization): void {
            $organization->loadMissing('defaultPlan');

            Subscription::query()
                ->whereIn('customer_id', $organization->customers()->select('id'))
                ->where('status', '!=', 'cancelled')
                ->with('items')
                ->chunkById(100, function ($subscriptions) use ($organization): void {
                    foreach ($subscriptions as $subscription) {
                        $this->applyDefaultsToExistingSubscription($subscription, $organization);
                    }
                });
        });
    }

    public function applyDefaultsToExistingSubscription(Subscription $subscription, Organization $organization): void
    {
        $organization->loadMissing('defaultPlan');

        $subscription->update([
            'plan_id' => $organization->default_plan_id,
            'base_price' => $organization->defaultPlan?->price ?? $subscription->base_price,
            'billing_cycle' => $organization->default_billing_cycle,
            'billing_enabled' => true,
            'billing_disabled_at' => null,
            'grace_period_days' => $organization->default_grace_period_days,
        ]);

        $planItem = $subscription->items()->where('item_type', 'plan')->oldest()->first();

        if ($planItem) {
            $this->applyDefaultsToPlanItem($planItem, $organization);
        }

        $subscription->calculateTotalPrice();
    }

    public function applyDefaultsToPlanItem(SubscriptionItem $item, Organization $organization): void
    {
        $organization->loadMissing('defaultPlan');
        $item->loadMissing('subscription.customer');
        $tax = app(TaxResolverService::class)->resolve(
            $item->subscription->customer,
            $organization->defaultPlan,
            'plan',
        );

        $item->fill([
            'plan_id' => $organization->default_plan_id,
            'description' => $organization->defaultPlan?->name ?? $item->description,
            'unit_price' => $organization->defaultPlan?->price ?? $item->unit_price,
            'discount_type' => $organization->default_discount_type,
            'discount_amount' => $organization->default_discount_amount,
            'tax_percentage' => $tax['percentage'],
            'recurring' => true,
            'billing_cycle' => $organization->default_billing_cycle,
        ]);
        $item->calculateTotals();
        $item->save();
    }

    public function organizationForCustomerId(int $customerId): ?Organization
    {
        return Organization::query()
            ->whereHas('customers', fn ($query) => $query->whereKey($customerId))
            ->with('defaultPlan')
            ->first();
    }

    public function assertPlanAllowedForCustomer(int $customerId, ?int $planId): void
    {
        $organization = $this->organizationForCustomerId($customerId);

        if (! $organization?->billing_enabled) {
            return;
        }

        $this->ensurePlanMatches($organization, (int) $planId);
    }

    protected function ensurePlanMatches(Organization $organization, int $planId): void
    {
        if ($planId === (int) $organization->default_plan_id) {
            return;
        }

        throw ValidationException::withMessages([
            'plan_id' => 'This customer belongs to an organization with billing enabled. Use the organization default service.',
        ]);
    }
}
