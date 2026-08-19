<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionOrganizationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_inherits_organization_group_before_customer_group(): void
    {
        $tenant = $this->tenant();
        $customerGroup = $this->group($tenant, 'Customer Group');
        $organizationGroup = $this->group($tenant, 'Organization Group');
        $customer = $this->customer($tenant, $customerGroup);
        $organization = $this->organization($tenant, $organizationGroup, 'North');

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'organization_id' => $organization->id,
            'subscription_code' => 'SUB-ORG-001',
            'name' => 'North Circuit',
            'service_type' => 'hotspot',
            'status' => 'pending',
        ]);

        $this->assertSame($organizationGroup->id, $subscription->user_group_id);
        $this->assertTrue($subscription->organization->is($organization));
    }

    public function test_user_only_sees_subscriptions_owned_by_organizations_in_their_group(): void
    {
        $tenant = $this->tenant();
        $visibleGroup = $this->group($tenant, 'Visible');
        $hiddenGroup = $this->group($tenant, 'Hidden');
        $customer = $this->customer($tenant, $visibleGroup);
        $visibleOrganization = $this->organization($tenant, $visibleGroup, 'Visible');
        $hiddenOrganization = $this->organization($tenant, $hiddenGroup, 'Hidden');
        $visible = $this->subscription($tenant, $customer, $visibleOrganization, 'SUB-VISIBLE');
        $hidden = $this->subscription($tenant, $customer, $hiddenOrganization, 'SUB-HIDDEN');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $visibleGroup->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $this->assertTrue(Subscription::query()->whereKey($visible)->exists());
        $this->assertFalse(Subscription::query()->whereKey($hidden)->exists());
    }

    private function tenant(): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Organization Ownership',
            'slug' => 'organization-ownership',
            'company_name' => 'Organization Ownership',
            'email' => 'ownership@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function group(Tenant $tenant, string $name): UserGroup
    {
        return UserGroup::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'name' => $name]);
    }

    private function organization(Tenant $tenant, UserGroup $group, string $name): Organization
    {
        return Organization::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $group->id,
            'code' => 'ORG-'.Str::upper($name),
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function customer(Tenant $tenant, UserGroup $group): Customer
    {
        return Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $group->id,
            'customer_code' => 'CUST-ORG',
            'customer_type' => 'individual',
            'first_name' => 'Shared',
            'last_name' => 'Customer',
            'name' => 'Shared Customer',
            'email' => 'shared@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);
    }

    private function subscription(Tenant $tenant, Customer $customer, Organization $organization, string $code): Subscription
    {
        return Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'organization_id' => $organization->id,
            'subscription_code' => $code,
            'name' => $code,
            'service_type' => 'hotspot',
            'status' => 'pending',
        ]);
    }
}
