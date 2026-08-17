<?php

namespace Tests\Feature;

use App\Models\Customer;
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
}
