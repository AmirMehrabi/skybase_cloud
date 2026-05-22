<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Models\RadiusUserGroup;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RadiusProvisioningService
{
    public function syncSubscription(Subscription $subscription, ?string $previousUsername = null): void
    {
        $subscription->loadMissing(['customer.organization', 'plan']);

        DB::transaction(function () use ($subscription, $previousUsername): void {
            if ($previousUsername && $previousUsername !== $subscription->pppoe_username) {
                $this->removeUsername((string) $subscription->tenant_id, $previousUsername);
            }

            if (! $this->isProvisionable($subscription)) {
                $this->removeUsername((string) $subscription->tenant_id, (string) $subscription->pppoe_username);

                return;
            }

            $tenantId = (string) $subscription->tenant_id;
            $username = (string) $subscription->pppoe_username;
            $plan = $subscription->plan;
            $rateLimit = $this->rateLimitForPlan($plan);
            if ($rateLimit === null) {
                $this->removeUsername($tenantId, $username);

                return;
            }

            $groupName = $this->groupNameForPlan($plan);

            RadiusCheck::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'username' => $username,
                    'attribute' => 'Cleartext-Password',
                ],
                [
                    'op' => ':=',
                    'value' => (string) $subscription->pppoe_password,
                ],
            );

            RadiusReply::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'username' => $username,
                    'attribute' => 'Mikrotik-Rate-Limit',
                ],
                [
                    'op' => ':=',
                    'value' => $rateLimit,
                ],
            );

            RadiusUserGroup::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('username', $username)
                ->where('groupname', '!=', $groupName)
                ->delete();

            RadiusUserGroup::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'username' => $username,
                    'groupname' => $groupName,
                ],
                [
                    'priority' => 1,
                ],
            );
        });
    }

    public function removeSubscription(Subscription $subscription): void
    {
        $this->removeUsername((string) $subscription->tenant_id, (string) $subscription->pppoe_username);
    }

    public function syncSubscriptionsForPlan(Plan $plan): void
    {
        Subscription::withoutGlobalScopes()
            ->where('plan_id', $plan->id)
            ->with(['customer.organization', 'plan'])
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $this->syncSubscription($subscription);
                }
            });
    }

    public function syncSubscriptionsForCustomer(Customer $customer): void
    {
        Subscription::withoutGlobalScopes()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->with(['customer.organization', 'plan'])
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $this->syncSubscription($subscription);
                }
            });
    }

    public function removeUsername(string $tenantId, string $username): void
    {
        if ($tenantId === '' || $username === '') {
            return;
        }

        RadiusCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->delete();

        RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->delete();

        RadiusUserGroup::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->delete();
    }

    public function rateLimitForPlan(?Plan $plan): ?string
    {
        if (! $plan || ! $plan->upload_speed || ! $plan->download_speed) {
            return null;
        }

        $suffix = $this->rateSuffix((string) $plan->bandwidth_unit);

        return ((int) $plan->upload_speed).$suffix.'/'.((int) $plan->download_speed).$suffix;
    }

    protected function isProvisionable(Subscription $subscription): bool
    {
        if ((string) $subscription->tenant_id === '') {
            return false;
        }

        if (! $subscription->isPppoe()) {
            return false;
        }

        if ($subscription->status !== 'active' || ! $subscription->billing_enabled) {
            return false;
        }

        if (! $subscription->pppoe_username || ! $subscription->pppoe_password) {
            return false;
        }

        if (! $subscription->customer?->billing_enabled) {
            return false;
        }

        if ($subscription->customer?->organization?->billing_enabled
            && (int) $subscription->customer->organization->default_plan_id !== (int) $subscription->plan_id) {
            return false;
        }

        return $subscription->plan?->status === 'active'
            && $this->rateLimitForPlan($subscription->plan) !== null;
    }

    protected function rateSuffix(string $unit): string
    {
        return match (strtolower($unit)) {
            'kbps', 'kbit', 'kbits' => 'k',
            'gbps', 'gbit', 'gbits' => 'G',
            default => 'M',
        };
    }

    protected function groupNameForPlan(?Plan $plan): string
    {
        if (! $plan) {
            return 'skybase-plan-unassigned';
        }

        $name = $plan->router_profile ?: $plan->internal_name ?: 'plan-'.$plan->id;

        return 'skybase-plan-'.Str::slug($name);
    }
}
