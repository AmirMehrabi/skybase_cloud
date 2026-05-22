<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Ldap\LdapSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LdapOrganizationSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_organizations_links_customers_and_applies_organization_billing_to_subscriptions(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $plan = Plan::factory()->create([
            'status' => 'active',
            'name' => 'Enterprise Fiber',
            'price' => 100,
            'billing_cycle' => 'monthly',
        ]);

        $settings = LdapSyncService::defaultSettings();
        $settings['connection']['enabled'] = true;
        $settings['connection']['missing_action'] = 'ignore';
        $settings['organization_sync']['unique_attribute'] = 'objectGUID';
        $settings['organization_sync']['map']['code'] = 'sAMAccountName';
        $settings['organization_sync']['map']['name'] = 'cn';
        $settings['customer_sync']['unique_attribute'] = 'uid';
        $settings['customer_sync']['organization_attribute'] = 'department';
        $settings['customer_sync']['organization_match_field'] = 'code';
        $settings['customer_sync']['map']['customer_code'] = 'uid';
        $settings['customer_sync']['map']['name'] = 'cn';
        $settings['subscription_sync']['unique_attribute'] = 'uid';
        $settings['subscription_sync']['customer_attribute'] = 'customerUid';
        $settings['subscription_sync']['customer_match_field'] = 'customer_code';
        $settings['subscription_sync']['map']['subscription_code'] = 'uid';
        $settings['subscription_sync']['map']['pppoe_username'] = 'uid';

        $result = app(LdapSyncService::class)->syncTenantFromEntries(
            $tenant,
            $settings,
            [[
                'uid' => 'cust-100',
                'cn' => 'Jane Subscriber',
                'department' => 'enterprise',
                'dn' => 'uid=cust-100,ou=customers,dc=alpha,dc=test',
            ]],
            [[
                'uid' => 'sub-200',
                'customerUid' => 'cust-100',
                'dn' => 'uid=sub-200,ou=subscriptions,dc=alpha,dc=test',
            ]],
            false,
            [[
                'objectGUID' => 'org-enterprise',
                'sAMAccountName' => 'enterprise',
                'cn' => 'Enterprise Accounts',
                'dn' => 'cn=enterprise,ou=organizations,dc=alpha,dc=test',
            ]],
        );

        $organization = Organization::withoutGlobalScopes()->where('code', 'enterprise')->firstOrFail();
        $organization->update([
            'billing_enabled' => true,
            'default_plan_id' => $plan->id,
            'default_billing_cycle' => 'monthly',
            'default_grace_period_days' => 14,
            'default_discount_type' => 'percentage',
            'default_discount_amount' => 10,
            'default_tax_percentage' => 8,
        ]);

        app(LdapSyncService::class)->syncTenantFromEntries(
            $tenant,
            $settings,
            [[
                'uid' => 'cust-100',
                'cn' => 'Jane Subscriber',
                'department' => 'enterprise',
                'dn' => 'uid=cust-100,ou=customers,dc=alpha,dc=test',
            ]],
            [[
                'uid' => 'sub-200',
                'customerUid' => 'cust-100',
                'dn' => 'uid=sub-200,ou=subscriptions,dc=alpha,dc=test',
            ]],
            false,
            [],
        );

        $customer = Customer::withoutGlobalScopes()->where('customer_code', 'cust-100')->firstOrFail();
        $subscription = Subscription::withoutGlobalScopes()->where('subscription_code', 'sub-200')->firstOrFail();
        $item = $subscription->items()->where('item_type', 'plan')->firstOrFail();

        $this->assertSame(1, $result['organizations']['created']);
        $this->assertSame($organization->id, $customer->organization_id);
        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame(14, $subscription->grace_period_days);
        $this->assertEquals(97.2, (float) $item->total);
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
