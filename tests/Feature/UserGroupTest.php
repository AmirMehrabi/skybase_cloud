<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_tenant_user_groups(): void
    {
        [$tenant, $owner] = $this->tenantUser('alpha', 'owner');

        $response = $this->actingAs($owner)->post(route('admin.tenant.user-groups.store'), [
            'name' => 'Reseller North',
            'description' => 'Northern reseller accounts',
        ]);

        $group = UserGroup::query()->firstOrFail();

        $response->assertRedirect(route('admin.tenant.user-groups.show', $group));
        $this->assertSame($tenant->id, $group->tenant_id);
        $this->assertSame('Reseller North', $group->name);
    }

    public function test_owner_can_assign_a_plan_to_a_user_group(): void
    {
        [$tenant, $owner] = $this->tenantUser('alpha', 'owner');
        $group = $this->group($tenant, 'Plan Group');

        $this->actingAs($owner)
            ->get(route('plans.create'))
            ->assertOk()
            ->assertSee('name="user_group_id"', false)
            ->assertSee('Plan Group');

        $this->actingAs($owner)
            ->post(route('plans.store'), $this->planPayload($group->id))
            ->assertSessionHasNoErrors();

        $plan = Plan::withoutGlobalScopes()->where('internal_name', 'group_plan')->firstOrFail();

        $this->assertSame($tenant->id, $plan->tenant_id);
        $this->assertSame($group->id, $plan->user_group_id);

        $this->actingAs($owner)
            ->get(route('plans.edit', $plan))
            ->assertOk()
            ->assertSee('name="user_group_id"', false);
    }

    public function test_plan_cannot_be_assigned_to_another_tenants_user_group(): void
    {
        [, $owner] = $this->tenantUser('alpha', 'owner');
        $otherTenant = $this->tenant('beta');
        $otherGroup = $this->group($otherTenant, 'Other Tenant Group');

        $this->actingAs($owner)
            ->postJson(route('plans.store'), $this->planPayload($otherGroup->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_group_id']);

        $this->assertDatabaseMissing('plans', ['internal_name' => 'group_plan']);
    }

    public function test_user_group_names_are_unique_per_tenant(): void
    {
        [$tenant, $owner] = $this->tenantUser('alpha', 'owner');
        $otherTenant = $this->tenant('beta');
        UserGroup::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'name' => 'Shared Name']);
        UserGroup::withoutGlobalScopes()->create(['tenant_id' => $otherTenant->id, 'name' => 'Shared Name']);

        $this->actingAs($owner)
            ->post(route('admin.tenant.user-groups.store'), ['name' => 'Shared Name'])
            ->assertSessionHasErrors('name');
    }

    public function test_non_owner_only_sees_records_from_their_group(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha', 'admin');
        $visibleGroup = $this->group($tenant, 'Visible');
        $hiddenGroup = $this->group($tenant, 'Hidden');
        $user->forceFill(['user_group_id' => $visibleGroup->id])->save();

        $visible = $this->customer($tenant, 'Visible Customer', $visibleGroup);
        $hidden = $this->customer($tenant, 'Hidden Customer', $hiddenGroup);

        $this->actingAs($user);

        $this->assertTrue(Customer::query()->whereKey($visible)->exists());
        $this->assertFalse(Customer::query()->whereKey($hidden)->exists());
        $this->get(route('customers.show', $hidden))->assertNotFound();
    }

    public function test_non_owner_sees_customer_through_a_secondary_organization_in_their_group(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha', 'admin');
        $visibleGroup = $this->group($tenant, 'Visible');
        $hiddenGroup = $this->group($tenant, 'Hidden');
        $user->forceFill(['user_group_id' => $visibleGroup->id])->save();
        $visibleOrganization = Organization::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $visibleGroup->id,
            'name' => 'Visible Organization',
            'code' => 'ORG-VISIBLE',
            'status' => 'active',
            'billing_enabled' => false,
        ]);
        $hiddenOrganization = Organization::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'name' => 'Hidden Organization',
            'code' => 'ORG-HIDDEN',
            'status' => 'active',
            'billing_enabled' => false,
        ]);
        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'organization_id' => $hiddenOrganization->id,
            'customer_code' => 'CUST-SECONDARY-ORG',
            'customer_type' => 'individual',
            'first_name' => 'Shared',
            'last_name' => 'Customer',
            'name' => 'Shared Customer',
            'email' => 'shared-customer@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);
        $customer->organizations()->syncWithPivotValues(
            [$visibleOrganization->id, $hiddenOrganization->id],
            ['tenant_id' => $tenant->id],
        );

        $this->actingAs($user);

        $this->assertTrue(Customer::query()->whereKey($customer)->exists());
        $this->get(route('subscriptions.create'))
            ->assertOk()
            ->assertViewHas('customers', fn ($customers): bool => $customers->contains('id', $customer->id));

        $subscription = Subscription::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'customer_id' => $customer->id,
            'organization_id' => $hiddenOrganization->id,
            'subscription_code' => 'SUB-SECONDARY-ORG',
            'name' => 'Shared Subscription',
            'service_type' => 'hotspot',
            'status' => 'pending',
        ]);
        $subscription->organizations()->syncWithPivotValues(
            [$visibleOrganization->id, $hiddenOrganization->id],
            ['tenant_id' => $tenant->id],
        );

        $this->assertTrue(Subscription::query()->whereKey($subscription)->exists());
    }

    public function test_subscription_form_only_shows_group_plans_and_routers(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha', 'admin');
        $visibleGroup = $this->group($tenant, 'Visible');
        $hiddenGroup = $this->group($tenant, 'Hidden');
        $user->forceFill(['user_group_id' => $visibleGroup->id])->save();
        $visiblePlan = Plan::factory()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $visibleGroup->id,
            'name' => 'Visible Plan',
            'status' => 'active',
        ]);
        $hiddenPlan = Plan::factory()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'name' => 'Hidden Plan',
            'status' => 'active',
        ]);
        $visibleOrganization = Organization::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $visibleGroup->id,
            'name' => 'Visible Organization',
            'code' => 'ORG-PLAN-VISIBLE',
            'status' => 'active',
            'billing_enabled' => true,
            'default_plan_id' => $visiblePlan->id,
        ]);
        Organization::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'name' => 'Hidden Organization',
            'code' => 'ORG-PLAN-HIDDEN',
            'status' => 'active',
            'billing_enabled' => true,
            'default_plan_id' => $hiddenPlan->id,
        ]);
        $visibleSite = Site::factory()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $visibleGroup->id,
            'code' => 'SITE-VISIBLE',
            'name' => 'Visible Site',
            'status' => 'active',
        ]);
        $hiddenSite = Site::factory()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'code' => 'SITE-HIDDEN',
            'name' => 'Hidden Site',
            'status' => 'active',
        ]);
        $visibleRouter = Router::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'site_id' => $visibleSite->id,
            'name' => 'Visible Router',
            'ip_address' => '192.0.2.10',
            'status' => 'online',
        ]);
        $visibleRouter->forceFill(['user_group_id' => null])->saveQuietly();
        $hiddenRouter = Router::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'site_id' => $hiddenSite->id,
            'name' => 'Hidden Router',
            'ip_address' => '192.0.2.20',
            'status' => 'online',
        ]);
        $hiddenRouter->forceFill(['user_group_id' => null])->saveQuietly();
        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $visibleGroup->id,
            'organization_id' => $visibleOrganization->id,
            'customer_code' => 'CUST-GROUP-SERVICE',
            'customer_type' => 'individual',
            'first_name' => 'Group',
            'last_name' => 'Service',
            'name' => 'Group Service Customer',
            'email' => 'group-service@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);
        $customer->organizations()->syncWithPivotValues(
            [$visibleOrganization->id],
            ['tenant_id' => $tenant->id],
        );

        $this->actingAs($user);

        $this->assertTrue(Plan::query()->whereKey($visiblePlan)->exists());
        $this->assertFalse(Plan::query()->whereKey($hiddenPlan)->exists());
        $this->assertTrue(Router::query()->whereKey($visibleRouter)->exists());
        $this->assertFalse(Router::query()->whereKey($hiddenRouter)->exists());

        $this->get(route('subscriptions.create'))
            ->assertOk()
            ->assertViewHas('plans', fn ($plans): bool => $plans->contains('id', $visiblePlan->id)
                && ! $plans->contains('id', $hiddenPlan->id))
            ->assertViewHas('routers', fn ($routers): bool => $routers->contains('id', $visibleRouter->id)
                && ! $routers->contains('id', $hiddenRouter->id));

        $this->postJson(route('subscriptions.store'), [
            'organization_ids' => [$visibleOrganization->id],
            'customer_id' => $customer->id,
            'name' => 'Unauthorized Resources',
            'service_type' => 'hotspot',
            'plan_id' => $hiddenPlan->id,
            'router_id' => $hiddenRouter->id,
            'connection_type' => 'static',
            'billing_enabled' => true,
            'status' => 'active',
            'items' => [[
                'item_type' => 'plan',
                'description' => $hiddenPlan->name,
                'quantity' => 1,
                'unit_price' => 50,
                'discount_amount' => 0,
                'discount_type' => 'none',
                'tax_percentage' => 0,
                'recurring' => true,
                'billing_cycle' => 'monthly',
            ]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['plan_id', 'router_id']);
    }

    public function test_secondary_site_group_membership_scopes_site_router_and_subscription_options(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha', 'admin');
        $primaryGroup = $this->group($tenant, 'Primary Site Group');
        $secondaryGroup = $this->group($tenant, 'Secondary Site Group');
        $hiddenGroup = $this->group($tenant, 'Hidden Site Group');
        $user->forceFill(['user_group_id' => $secondaryGroup->id])->save();

        $site = Site::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $primaryGroup->id,
            'code' => 'SHARED-SITE',
            'name' => 'Shared Site',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'status' => 'active',
        ]);
        $site->userGroups()->syncWithPivotValues(
            [$primaryGroup->id, $secondaryGroup->id],
            ['tenant_id' => $tenant->id],
        );
        $router = Router::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $primaryGroup->id,
            'site_id' => $site->id,
            'name' => 'Shared Site Router',
            'ip_address' => '192.0.2.50',
            'status' => 'online',
        ]);
        $visiblePlan = Plan::withoutGlobalScopes()->create([
            ...Plan::factory()->raw(),
            'tenant_id' => $tenant->id,
            'user_group_id' => $secondaryGroup->id,
            'name' => 'Secondary Group Plan',
            'internal_name' => 'secondary_group_plan',
            'status' => 'active',
        ]);
        $hiddenPlan = Plan::withoutGlobalScopes()->create([
            ...Plan::factory()->raw(),
            'tenant_id' => $tenant->id,
            'user_group_id' => $hiddenGroup->id,
            'name' => 'Hidden Site Plan',
            'internal_name' => 'hidden_site_plan',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $this->assertTrue(Site::query()->whereKey($site)->exists());
        $this->assertTrue(Router::query()->whereKey($router)->exists());
        $this->get(route('subscriptions.create'))
            ->assertOk()
            ->assertViewHas('plans', fn ($plans): bool => $plans->contains('id', $visiblePlan->id)
                && ! $plans->contains('id', $hiddenPlan->id))
            ->assertViewHas('routers', fn ($routers): bool => $routers->contains('id', $router->id));
    }

    public function test_owner_bypasses_group_scope_but_not_tenant_scope(): void
    {
        [$tenant, $owner] = $this->tenantUser('alpha', 'owner');
        $otherTenant = $this->tenant('beta');
        $first = $this->customer($tenant, 'First', $this->group($tenant, 'First Group'));
        $second = $this->customer($tenant, 'Second', $this->group($tenant, 'Second Group'));
        $foreign = $this->customer($otherTenant, 'Foreign', $this->group($otherTenant, 'Foreign Group'));

        $this->actingAs($owner);

        $this->assertTrue(Customer::query()->whereKey($first)->exists());
        $this->assertTrue(Customer::query()->whereKey($second)->exists());
        $this->assertFalse(Customer::query()->whereKey($foreign)->exists());
    }

    public function test_ungrouped_non_owner_only_sees_ungrouped_records(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha', 'admin');
        $ungrouped = $this->customer($tenant, 'Ungrouped');
        $grouped = $this->customer($tenant, 'Grouped', $this->group($tenant, 'Group'));

        $this->actingAs($user);

        $this->assertTrue(Customer::query()->whereKey($ungrouped)->exists());
        $this->assertFalse(Customer::query()->whereKey($grouped)->exists());
    }

    public function test_subscription_inherits_its_customer_group(): void
    {
        [$tenant, $user] = $this->tenantUser('alpha', 'admin');
        $group = $this->group($tenant, 'Accounts');
        $user->forceFill(['user_group_id' => $group->id])->save();
        $customer = $this->customer($tenant, 'Customer', $group);

        $this->actingAs($user);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-001',
            'name' => 'Internet',
            'service_type' => 'hotspot',
            'status' => 'pending',
        ]);

        $this->assertSame($group->id, $subscription->user_group_id);
    }

    public function test_assigned_group_cannot_be_deleted(): void
    {
        [$tenant, $owner] = $this->tenantUser('alpha', 'owner');
        $group = $this->group($tenant, 'Assigned');
        $this->customer($tenant, 'Customer', $group);

        $this->actingAs($owner)
            ->delete(route('admin.tenant.user-groups.destroy', $group))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('user_groups', ['id' => $group->id]);
    }

    /** @return array{Tenant, User} */
    private function tenantUser(string $slug, string $role): array
    {
        $tenant = $this->tenant($slug);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
        ]);

        return [$tenant, $user];
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => "{$slug}@example.com",
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function group(Tenant $tenant, string $name): UserGroup
    {
        return UserGroup::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
        ]);
    }

    private function customer(Tenant $tenant, string $name, ?UserGroup $group = null): Customer
    {
        return Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_group_id' => $group?->id,
            'customer_code' => 'CUST-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'first_name' => $name,
            'last_name' => 'Account',
            'name' => "{$name} Account",
            'email' => Str::slug($name).Str::random(5).'@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function planPayload(int $userGroupId): array
    {
        return [
            'user_group_id' => $userGroupId,
            'name' => 'Group Plan',
            'internal_name' => 'group_plan',
            'status' => 'active',
            'visibility' => 'public',
            'type' => 'pppoe',
            'download_speed' => 100,
            'upload_speed' => 20,
            'burst_download' => 0,
            'burst_upload' => 0,
            'bandwidth_unit' => 'Mbps',
            'shaping_mode' => 'basic',
            'data_limit' => 100,
            'data_unit' => 'GB',
            'data_cap_action' => 'none',
            'unlimited' => false,
            'price' => 50,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'grace_period_days' => 7,
            'setup_fee' => 0,
            'priority' => 5,
            'contract_required' => false,
        ];
    }
}
