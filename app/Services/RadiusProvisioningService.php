<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Models\RadiusUserGroup;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\TrafficShaping\PlanTrafficShapingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RadiusProvisioningService
{
    public function __construct(
        protected PlanTrafficShapingService $trafficShaping,
    ) {}

    public function syncSubscription(Subscription $subscription, ?string $previousUsername = null): void
    {
        $subscription->loadMissing(['customer.organization', 'plan']);

        DB::transaction(function () use ($subscription, $previousUsername): void {
            if ($previousUsername && $previousUsername !== $subscription->pppoe_username) {
                $this->removeUsername((string) $subscription->tenant_id, $previousUsername);
            }

            $skipReason = $this->provisioningSkipReason($subscription);
            if ($skipReason !== null) {
                Log::info('Radius provisioning skipped', [
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'subscription_code' => $subscription->subscription_code,
                    'pppoe_username' => $subscription->pppoe_username,
                    'reason' => $skipReason,
                ]);

                $this->removeUsername((string) $subscription->tenant_id, (string) $subscription->pppoe_username);

                if ($subscription->status === 'suspended' || $subscription->customer?->status === 'suspended') {
                    $this->rejectUsername((string) $subscription->tenant_id, (string) $subscription->pppoe_username);
                }

                return;
            }

            $tenantId = (string) $subscription->tenant_id;
            $username = (string) $subscription->pppoe_username;
            $plan = $subscription->plan;
            $groupName = $this->groupNameForPlan($plan);

            $this->removeReject($tenantId, $username);

            if ($this->usesLdapRadiusAuthentication($tenantId)) {
                $this->removePasswordChecks($tenantId, $username);
            } else {
                $password = (string) $subscription->pppoe_password;

                RadiusCheck::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'username' => $username,
                        'attribute' => 'Cleartext-Password',
                    ],
                    [
                        'op' => ':=',
                        'value' => $password,
                    ],
                );

                RadiusCheck::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'username' => $username,
                        'attribute' => 'NT-Password',
                    ],
                    [
                        'op' => ':=',
                        'value' => $this->makeNtPasswordHash($password),
                    ],
                );
            }

            if (! empty($subscription->ip_address)) {
                RadiusReply::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'username' => $username,
                        'attribute' => 'Framed-IP-Address',
                    ],
                    [
                        'op' => ':=',
                        'value' => (string) $subscription->ip_address,
                    ],
                );
            } else {
                RadiusReply::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('username', $username)
                    ->where('attribute', 'Framed-IP-Address')
                    ->delete();
            }

            $rateLimit = $this->rateLimitForPlan($plan);
            if ($rateLimit !== null) {
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
            } else {
                RadiusReply::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $username)->where('attribute', 'Mikrotik-Rate-Limit')->delete();
            }

            RadiusUserGroup::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $username)->where('groupname', '!=', $groupName)->delete();

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

    private function makeNtPasswordHash(string $password): string
    {
        return strtoupper(hash('md4', mb_convert_encoding($password, 'UTF-16LE', 'UTF-8')));
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

    public function syncSubscriptionsForTenant(string $tenantId): void
    {
        Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
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

        RadiusCheck::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $username)->delete();

        RadiusReply::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $username)->delete();

        RadiusUserGroup::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $username)->delete();
    }

    public function rejectUsername(string $tenantId, string $username): void
    {
        if ($tenantId === '' || $username === '') {
            return;
        }

        RadiusCheck::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'username' => $username,
                'attribute' => 'Auth-Type',
            ],
            [
                'op' => ':=',
                'value' => 'Reject',
            ],
        );
    }

    private function removeReject(string $tenantId, string $username): void
    {
        RadiusCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->where('attribute', 'Auth-Type')
            ->where('value', 'Reject')
            ->delete();
    }

    public function rateLimitForPlan(?Plan $plan): ?string
    {
        return $this->trafficShaping->mikrotikRateLimit($plan);
    }

    public function provisioningSkipReason(Subscription $subscription): ?string
    {
        if ((string) $subscription->tenant_id === '') {
            return 'missing tenant_id';
        }

        if (! $subscription->isPppoe()) {
            return 'connection type is not pppoe';
        }

        if ($subscription->status !== 'active') {
            return 'subscription status is not active';
        }

        if (! $subscription->pppoe_username) {
            return 'missing pppoe username';
        }

        if ($subscription->customer?->status !== 'active') {
            return 'customer status is not active';
        }

        if (! $this->usesLdapRadiusAuthentication((string) $subscription->tenant_id) && ! $subscription->pppoe_password) {
            return 'missing pppoe password';
        }

        if ($subscription->customer?->organization?->billing_enabled && (int) $subscription->customer->organization->default_plan_id !== (int) $subscription->plan_id) {
            return 'subscription plan does not match organization billing plan';
        }

        if ($subscription->plan?->status !== 'active') {
            return 'plan is not active';
        }

        return null;
    }

    protected function groupNameForPlan(?Plan $plan): string
    {
        if (! $plan) {
            return 'skybase-plan-unassigned';
        }

        $name = $plan->router_profile ?: $plan->internal_name ?: 'plan-'.$plan->id;

        return 'skybase-plan-'.Str::slug($name);
    }

    private function usesLdapRadiusAuthentication(string $tenantId): bool
    {
        $settings = Setting::get('ldap.radius_auth', [], $tenantId) ?? [];

        return (bool) ($settings['enabled'] ?? false);
    }

    private function removePasswordChecks(string $tenantId, string $username): void
    {
        RadiusCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('username', $username)
            ->whereIn('attribute', ['Cleartext-Password', 'NT-Password'])
            ->delete();
    }
}
