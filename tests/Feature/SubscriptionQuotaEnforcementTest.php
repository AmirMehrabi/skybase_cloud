<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusUserGroup;
use App\Models\Subscription;
use App\Models\SubscriptionRestriction;
use App\Models\Tenant;
use App\Services\SubscriptionUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionQuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_combined_radius_usage_restricts_and_bonus_data_restores_subscription(): void
    {
        [$subscription, $tenant] = $this->subscriptionWithOneMegabyteLimit();
        DB::table('radacct')->insert([
            'acctsessionid' => 'quota-session', 'acctuniqueid' => 'quota-unique',
            'username' => $subscription->pppoe_username, 'acctstarttime' => now()->subMinute(),
            'acctupdatetime' => now(), 'acctinputoctets' => 524288, 'acctoutputoctets' => 524288,
        ]);

        $usage = app(SubscriptionUsageService::class);
        $cycle = $usage->reconcile($subscription);

        $this->assertSame(1048576, $cycle->usedBytes());
        $this->assertTrue(SubscriptionRestriction::withoutGlobalScopes()->where('subscription_id', $subscription->id)->active()->where('type', 'quota')->exists());
        $this->assertDatabaseHas('radusergroup', ['tenant_id' => $tenant->id, 'username' => 'quota.user', 'groupname' => 'skybase-restricted']);

        $usage->addData($subscription, 'bonus', 1048576, 'Retention bonus');

        $this->assertFalse(SubscriptionRestriction::withoutGlobalScopes()->where('subscription_id', $subscription->id)->active()->where('type', 'quota')->exists());
        $this->assertSame(0, RadiusUserGroup::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'quota.user')->where('groupname', 'skybase-restricted')->count());
    }

    private function subscriptionWithOneMegabyteLimit(): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(), 'name' => 'Quota ISP', 'slug' => 'quota-isp',
            'company_name' => 'Quota ISP', 'email' => 'quota@example.com', 'timezone' => 'UTC', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id, 'customer_code' => 'CUS-QUOTA', 'customer_type' => 'individual',
            'name' => 'Quota Customer', 'billing_type' => 'postpaid', 'billing_enabled' => true,
            'balance' => 0, 'credit_limit' => 0, 'tax_exempt' => false, 'status' => 'active',
        ]);
        $plan = Plan::factory()->create([
            'tenant_id' => $tenant->id, 'status' => 'active', 'type' => 'pppoe', 'data_limit' => 1,
            'data_unit' => 'MB', 'unlimited' => false, 'data_cap_action' => 'suspend', 'billing_cycle' => 'monthly',
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'plan_id' => $plan->id,
            'subscription_code' => 'SUB-QUOTA', 'connection_type' => 'pppoe', 'pppoe_username' => 'quota.user',
            'pppoe_password' => 'secret', 'billing_cycle' => 'monthly', 'billing_enabled' => true,
            'status' => 'active', 'start_date' => now()->startOfMonth(),
        ]);

        return [$subscription, $tenant];
    }
}
