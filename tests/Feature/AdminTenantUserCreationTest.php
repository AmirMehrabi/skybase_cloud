<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTenantUserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_tenant_users_inherit_the_current_tenant_id(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tenant.users.store'), [
            'name' => 'New Tenant User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'support',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.tenant.users.index'));
        $response->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'new-user@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertTrue(Hash::check('password123', $user->password));
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
