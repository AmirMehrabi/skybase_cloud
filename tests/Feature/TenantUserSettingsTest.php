<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantUserSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_settings_index_shows_explicit_edit_action(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'support',
            'status' => 'active',
            'name' => 'Support User',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tenant.users.index'));

        $response->assertOk();
        $response->assertSee('Edit');
        $response->assertSee(route('admin.tenant.users.edit', $admin), false);
    }

    public function test_user_update_keeps_disabled_role_and_status_fields_when_changing_password(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Owner',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'status' => 'active',
            'password' => Hash::make('original-password'),
        ]);

        $response = $this->actingAs($owner)->put(route('admin.tenant.users.update', $owner), [
            'name' => 'Tenant Owner',
            'email' => 'owner@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('admin.tenant.users.index'));
        $response->assertSessionHasNoErrors();

        $owner->refresh();

        $this->assertSame('owner', $owner->role);
        $this->assertSame('active', $owner->status);
        $this->assertTrue(Hash::check('new-password123', $owner->password));
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
