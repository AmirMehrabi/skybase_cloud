<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Ldap\LdapSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LdapSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_customers_and_subscriptions_for_the_current_tenant(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $otherTenant = $this->createTenant('beta-net');
        $settings = $this->settings();

        Customer::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'customer_code' => 'cust-100',
            'customer_type' => 'individual',
            'name' => 'Other Tenant',
            'first_name' => 'Other',
            'last_name' => 'Tenant',
            'status' => 'active',
            'billing_type' => 'prepaid',
            'ldap_guid' => 'cust-100',
        ]);

        $result = app(LdapSyncService::class)->syncTenantFromEntries(
            $tenant,
            $settings,
            [[
                'uid' => 'cust-100',
                'cn' => 'Jane Subscriber',
                'mail' => 'jane@example.test',
                'telephoneNumber' => '555-0101',
                'dn' => 'uid=cust-100,ou=customers,dc=alpha,dc=test',
            ]],
            [[
                'uid' => 'sub-200',
                'customerUid' => 'cust-100',
                'userPassword' => 'radius-secret',
                'framedIPAddress' => '10.10.0.5',
                'dn' => 'uid=sub-200,ou=subscriptions,dc=alpha,dc=test',
            ]],
        );

        $this->assertSame(1, $result['customers']['created']);
        $this->assertSame(1, $result['subscriptions']['created']);

        $customer = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('customer_code', 'cust-100')
            ->firstOrFail();

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('subscription_code', 'sub-200')
            ->firstOrFail();

        $this->assertSame('Jane Subscriber', $customer->name);
        $this->assertSame('jane@example.test', $customer->email);
        $this->assertSame($customer->id, $subscription->customer_id);
        $this->assertSame('sub-200', $subscription->pppoe_username);
        $this->assertSame('10.10.0.5', $subscription->ip_address);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $otherTenant->id,
            'customer_code' => 'cust-100',
            'name' => 'Other Tenant',
        ]);
    }

    public function test_missing_synced_records_are_marked_inactive_and_cancelled(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $settings = $this->settings();

        $oldCustomer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'old-customer',
            'customer_type' => 'individual',
            'name' => 'Old Customer',
            'first_name' => 'Old',
            'last_name' => 'Customer',
            'status' => 'active',
            'billing_type' => 'prepaid',
            'ldap_guid' => 'old-customer',
            'ldap_synced_at' => now()->subDay(),
        ]);

        Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $oldCustomer->id,
            'subscription_code' => 'old-subscription',
            'connection_type' => 'pppoe',
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'ldap_guid' => 'old-subscription',
            'ldap_synced_at' => now()->subDay(),
        ]);

        $result = app(LdapSyncService::class)->syncTenantFromEntries(
            $tenant,
            $settings,
            [[
                'uid' => 'new-customer',
                'cn' => 'New Customer',
                'dn' => 'uid=new-customer,ou=customers,dc=alpha,dc=test',
            ]],
            [],
        );

        $this->assertSame(1, $result['customers']['missing']);
        $this->assertSame(1, $result['subscriptions']['missing']);
        $this->assertSame('inactive', $oldCustomer->fresh()->status);
        $this->assertSame('cancelled', Subscription::withoutGlobalScopes()->where('subscription_code', 'old-subscription')->firstOrFail()->status);
    }

    public function test_unchecked_ou_records_are_left_unchanged_when_missing_records_are_handled(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $settings = $this->settings();
        $settings['organization_sync']['unique_attribute'] = 'objectGUID';
        $settings['organization_sync']['map']['code'] = 'ou';
        $settings['organization_sync']['map']['name'] = 'ou';
        $settings['customer_sync']['organization_attribute'] = 'department';
        $settings['customer_sync']['organization_match_field'] = 'code';

        $skippedOrganization = Organization::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'code' => 'Skipped',
            'name' => 'Skipped',
            'status' => 'active',
            'ldap_guid' => 'skipped-ou',
            'ldap_dn' => 'ou=Skipped,dc=alpha,dc=test',
        ]);
        $skippedCustomer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $skippedOrganization->id,
            'customer_code' => 'skipped-customer',
            'customer_type' => 'individual',
            'name' => 'Skipped Customer',
            'first_name' => 'Skipped',
            'last_name' => 'Customer',
            'status' => 'active',
            'billing_type' => 'prepaid',
            'ldap_guid' => 'skipped-customer',
            'ldap_dn' => 'uid=skipped-customer,ou=Skipped,dc=alpha,dc=test',
        ]);
        Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $skippedCustomer->id,
            'subscription_code' => 'skipped-subscription',
            'connection_type' => 'pppoe',
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'ldap_guid' => 'skipped-subscription',
            'ldap_dn' => 'uid=skipped-subscription,ou=Skipped,dc=alpha,dc=test',
        ]);

        $result = app(LdapSyncService::class)->syncTenantFromEntries(
            $tenant,
            $settings,
            [[
                'uid' => 'selected-customer',
                'cn' => 'Selected Customer',
                'department' => 'Selected',
                'dn' => 'uid=selected-customer,ou=Selected,dc=alpha,dc=test',
            ]],
            [[
                'uid' => 'selected-subscription',
                'customerUid' => 'selected-customer',
                'dn' => 'uid=selected-subscription,ou=Selected,dc=alpha,dc=test',
            ]],
            false,
            [[
                'objectGUID' => 'selected-ou',
                'ou' => 'Selected',
                'dn' => 'ou=Selected,dc=alpha,dc=test',
            ]],
            ['ou=Selected,dc=alpha,dc=test'],
        );

        $this->assertSame(0, $result['organizations']['missing']);
        $this->assertSame(0, $result['customers']['missing']);
        $this->assertSame(0, $result['subscriptions']['missing']);
        $this->assertSame('active', $skippedOrganization->fresh()->status);
        $this->assertSame('active', $skippedCustomer->fresh()->status);
        $this->assertSame('active', Subscription::withoutGlobalScopes()->where('subscription_code', 'skipped-subscription')->firstOrFail()->status);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'customer_code' => 'selected-customer',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $settings = LdapSyncService::defaultSettings();
        $settings['connection']['enabled'] = true;
        $settings['connection']['missing_action'] = 'mark_inactive';
        $settings['customer_sync']['unique_attribute'] = 'uid';
        $settings['customer_sync']['map']['customer_code'] = 'uid';
        $settings['customer_sync']['map']['name'] = 'cn';
        $settings['customer_sync']['map']['email'] = 'mail';
        $settings['customer_sync']['map']['phone'] = 'telephoneNumber';
        $settings['subscription_sync']['unique_attribute'] = 'uid';
        $settings['subscription_sync']['customer_attribute'] = 'customerUid';
        $settings['subscription_sync']['customer_match_field'] = 'customer_code';
        $settings['subscription_sync']['map']['subscription_code'] = 'uid';
        $settings['subscription_sync']['map']['pppoe_username'] = 'uid';
        $settings['subscription_sync']['map']['pppoe_password'] = 'userPassword';
        $settings['subscription_sync']['map']['ip_address'] = 'framedIPAddress';

        return $settings;
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
