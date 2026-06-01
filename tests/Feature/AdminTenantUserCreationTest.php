<?php

namespace Tests\Feature;

use App\Http\Middleware\InitializeTenancy;
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

    public function test_tenant_user_index_falls_back_to_authenticated_users_tenant(): void
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
            'name' => 'New Tenant User',
            'email' => 'new-user@example.com',
        ]);

        $response = $this->withoutMiddleware(InitializeTenancy::class)
            ->actingAs($admin)
            ->get(route('admin.tenant.users.index'));

        $response->assertOk();
        $response->assertSee('New Tenant User');
        $response->assertSee('new-user@example.com');
    }

    public function test_tenant_user_index_search_remains_tenant_scoped(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $otherTenant = $this->createTenant('beta-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'support',
            'status' => 'active',
            'name' => 'Shared Search User',
            'email' => 'visible@example.com',
        ]);

        User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'role' => 'support',
            'status' => 'active',
            'name' => 'Shared Search User',
            'email' => 'hidden@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tenant.users.index', ['search' => 'Shared Search']));

        $response->assertOk();
        $response->assertSee('visible@example.com');
        $response->assertDontSee('hidden@example.com');
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
