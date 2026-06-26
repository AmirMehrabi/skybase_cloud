<?php

namespace Tests\Feature;

use App\Jobs\Subscriptions\ActivateSubscriptionJob;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\RadiusCheck;
use App\Models\RadiusReply;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RadiusProvisioningService;
use App\Services\TenantNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class RadiusProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_pppoe_subscription_pushes_radius_attributes(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => 'secret-pass',
        ]);

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'op' => ':=',
            'value' => '20M/100M',
        ]);

        $this->assertDatabaseHas('radusergroup', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'groupname' => 'skybase-plan-fiber-100',
            'priority' => 1,
        ]);
    }

    public function test_plan_speed_changes_update_radius_rate_limit(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $plan->update([
            'download_speed' => 250,
            'upload_speed' => 50,
        ]);

        $this->assertSame(
            '50M/250M',
            RadiusReply::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('username', 'jane.doe')
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->value('value'),
        );
    }

    public function test_advanced_plan_shaping_generates_routeros_rate_limit(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        $plan->update([
            'shaping_mode' => 'advanced',
            'download_speed' => 100,
            'upload_speed' => 20,
            'burst_download' => 150,
            'burst_upload' => 40,
            'burst_threshold_download' => 75,
            'burst_threshold_upload' => 15,
            'burst_time_download' => 10,
            'burst_time_upload' => 8,
            'min_download_speed' => 25,
            'min_upload_speed' => 5,
            'shaping_priority' => 6,
        ]);

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M 40M/150M 15M/75M 8/10 6 5M/25M',
        ]);
    }

    public function test_disabled_plan_shaping_removes_radius_rate_limit(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $plan->update([
            'shaping_mode' => 'disabled',
        ]);

        $this->assertSame(0, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', 'jane.doe')
            ->where('attribute', 'Mikrotik-Rate-Limit')
            ->count());
    }

    public function test_activating_pending_subscription_pushes_radius_entries(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $subscription = Subscription::create([
            ...$this->subscriptionAttributes($tenant, $customer, $plan),
            'status' => 'pending',
            'activation_date' => null,
        ]);

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'jane.doe')->count());

        Queue::fake();

        $this->actingAs($user)->post(route('subscriptions.activate', $subscription))->assertRedirect(route('subscriptions.show', $subscription));

        Queue::assertPushedOn('subscriptions', ActivateSubscriptionJob::class);

        Queue::assertPushed(ActivateSubscriptionJob::class, function (ActivateSubscriptionJob $job) use ($subscription, $tenant): bool {
            return $job->subscriptionId === $subscription->id
                && $job->tenantId === $tenant->id;
        });

        (new ActivateSubscriptionJob($subscription->id, $tenant->id))->handle(
            app(RadiusProvisioningService::class),
            app(TenantNotificationService::class),
        );

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M',
        ]);
    }

    public function test_active_pppoe_subscription_without_plan_speed_still_pushes_radcheck(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();
        $plan->update([
            'download_speed' => 0,
            'upload_speed' => 0,
        ]);

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);

        $this->assertSame(0, RadiusReply::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', 'jane.doe')
            ->where('attribute', 'Mikrotik-Rate-Limit')
            ->count());
    }

    public function test_ldap_radius_authentication_skips_local_password_checks_but_keeps_reply_policy(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Setting::create([
            'tenant_id' => $tenant->id,
            'key' => 'ldap.radius_auth',
            'value' => [
                'enabled' => true,
                'mode' => 'ldap_bind',
                'username_attribute' => 'sAMAccountName',
            ],
            'type' => 'json',
            'group' => 'ldap',
        ]);

        Subscription::create([
            ...$this->subscriptionAttributes($tenant, $customer, $plan),
            'pppoe_password' => null,
        ]);

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('username', 'jane.doe')
            ->whereIn('attribute', ['Cleartext-Password', 'NT-Password'])
            ->count());

        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M',
        ]);

        $this->assertDatabaseHas('radusergroup', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'groupname' => 'skybase-plan-fiber-100',
        ]);
    }

    public function test_billing_disabled_subscription_keeps_radius_authorization(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        $subscription = Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $subscription->update(['billing_enabled' => false]);

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M',
        ]);
        $this->assertDatabaseHas('radusergroup', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'groupname' => 'skybase-plan-fiber-100',
        ]);
    }

    public function test_subscription_created_with_billing_disabled_pushes_radius_authorization(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Subscription::create([
            ...$this->subscriptionAttributes($tenant, $customer, $plan),
            'billing_enabled' => false,
        ]);

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M',
        ]);
    }

    public function test_customer_billing_disabled_keeps_radius_authorization(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $customer->update(['billing_enabled' => false]);

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M',
        ]);
    }

    public function test_subscription_for_customer_with_billing_disabled_pushes_radius_authorization(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();
        $customer->update(['billing_enabled' => false]);

        Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Cleartext-Password',
            'value' => 'secret-pass',
        ]);
        $this->assertDatabaseHas('radreply', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value' => '20M/100M',
        ]);
    }

    public function test_suspended_subscription_removes_radius_authorization(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        $subscription = Subscription::create($this->subscriptionAttributes($tenant, $customer, $plan));

        $subscription->suspend();

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'jane.doe')->where('attribute', 'Cleartext-Password')->count());
        $this->assertSame(0, RadiusReply::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('username', 'jane.doe')->count());

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenant->id,
            'username' => 'jane.doe',
            'attribute' => 'Auth-Type',
            'op' => ':=',
            'value' => 'Reject',
        ]);
    }

    public function test_non_pppoe_subscription_is_ignored(): void
    {
        [$tenant, $customer, $plan] = $this->tenantCustomerAndPlan();

        Subscription::create([
            ...$this->subscriptionAttributes($tenant, $customer, $plan),
            'connection_type' => 'dhcp',
            'pppoe_username' => null,
            'pppoe_password' => null,
        ]);

        $this->assertSame(0, RadiusCheck::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, RadiusReply::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function test_same_username_is_isolated_by_tenant(): void
    {
        [$tenantA, $customerA, $planA] = $this->tenantCustomerAndPlan('alpha-net', 'AlphaNet');
        [$tenantB, $customerB, $planB] = $this->tenantCustomerAndPlan('beta-net', 'BetaNet');

        Subscription::create($this->subscriptionAttributes($tenantA, $customerA, $planA, 'SUB-A', 'shared.user', 'alpha-pass'));
        Subscription::create($this->subscriptionAttributes($tenantB, $customerB, $planB, 'SUB-B', 'shared.user', 'beta-pass'));

        $this->assertSame(2, RadiusCheck::withoutGlobalScopes()->where('username', 'shared.user')->where('attribute', 'Cleartext-Password')->count());

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenantA->id,
            'username' => 'shared.user',
            'value' => 'alpha-pass',
        ]);

        $this->assertDatabaseHas('radcheck', [
            'tenant_id' => $tenantB->id,
            'username' => 'shared.user',
            'value' => 'beta-pass',
        ]);
    }

    /**
     * @return array{Tenant, Customer, Plan}
     */
    private function tenantCustomerAndPlan(string $slug = 'alpha-net', string $name = 'AlphaNet'): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'company_name' => $name,
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => Str::random(8).'@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Fiber 100',
            'internal_name' => $slug === 'alpha-net' ? 'fiber_100' : Str::slug($slug, '_').'_fiber_100',
            'router_profile' => 'fiber_100',
            'status' => 'active',
            'type' => 'pppoe',
            'download_speed' => 100,
            'upload_speed' => 20,
            'bandwidth_unit' => 'Mbps',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);

        return [$tenant, $customer, $plan];
    }

    /**
     * @return array<string, mixed>
     */
    private function subscriptionAttributes(
        Tenant $tenant,
        Customer $customer,
        Plan $plan,
        string $code = 'SUB-TEST-0001',
        string $username = 'jane.doe',
        string $password = 'secret-pass',
    ): array {
        return [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => $code,
            'plan_id' => $plan->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => $username,
            'pppoe_password' => $password,
            'base_price' => 79.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 79.99,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
        ];
    }
}
