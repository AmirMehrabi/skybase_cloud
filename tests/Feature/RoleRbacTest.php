<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permissions_can_be_created_and_persisted(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tenant.roles.store'), [
            'name' => 'Helpdesk',
            'description' => 'Limited support role',
            'permissions' => ['customers.read', 'support_tickets.read', 'support_tickets.write'],
        ]);

        $role = Role::query()->where('tenant_id', $tenant->id)->where('name', 'Helpdesk')->first();

        $response->assertRedirect(route('admin.tenant.roles.show', $role));
        $this->assertSame(['customers.read', 'support_tickets.read', 'support_tickets.write'], $role->permissions);
    }

    public function test_user_without_permission_gets_farsi_denied_message_for_direct_page_access(): void
    {
        $tenant = $this->createTenant('alpha-net');
        Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Read Customers',
            'permissions' => ['customers.read'],
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'Read Customers',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('customers.create'));

        $response->assertForbidden();
        $response->assertSee(PermissionRegistry::DENIED_MESSAGE);
    }

    public function test_user_without_permission_gets_farsi_json_denial(): void
    {
        $tenant = $this->createTenant('alpha-net');
        Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'No Customers',
            'permissions' => ['dashboard.read'],
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'No Customers',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->getJson(route('customers.data'));

        $response->assertForbidden();
        $response->assertJson(['message' => PermissionRegistry::DENIED_MESSAGE]);
    }

    public function test_default_lowercase_admin_role_still_has_full_access_without_role_rows(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tenant.roles.index'));

        $response->assertOk();
        $response->assertSee('Role Management');
    }

    public function test_user_without_dashboard_permission_is_redirected_to_first_accessible_page(): void
    {
        $tenant = $this->createTenant('alpha-net');
        Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Customer Reader',
            'permissions' => ['customers.read'],
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'Customer Reader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('customers.index'));
    }

    public function test_assigned_role_cannot_be_deleted(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
        $role = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Support Desk',
            'permissions' => ['customers.read'],
        ]);
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'support desk',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.tenant.roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'این نقش به کاربر اختصاص داده شده و قابل حذف نیست.');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
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
