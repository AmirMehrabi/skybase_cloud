<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\VpnUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class VpnUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_vpn_user_can_be_created_for_current_tenant(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('vpn-users.store'), [
            'username' => 'road_warrior_01',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'active' => '1',
        ]);

        $response->assertRedirect(route('vpn-users.index'));
        $response->assertSessionHasNoErrors();

        $vpnUser = VpnUser::query()->where('username', 'road_warrior_01')->first();

        $this->assertNotNull($vpnUser);
        $this->assertSame($tenant->id, $vpnUser->tenant_id);
        $this->assertTrue($vpnUser->active);
        $this->assertTrue(Hash::check('secure-password', $vpnUser->password_hash));
    }

    public function test_vpn_user_can_be_updated_without_changing_password(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
        $vpnUser = VpnUser::factory()->create([
            'tenant_id' => $tenant->id,
            'username' => 'old_user',
            'password_hash' => Hash::make('original-password'),
            'active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('vpn-users.update', $vpnUser), [
            'username' => 'updated_user',
        ]);

        $response->assertRedirect(route('vpn-users.show', $vpnUser));
        $response->assertSessionHasNoErrors();

        $vpnUser->refresh();

        $this->assertSame('updated_user', $vpnUser->username);
        $this->assertFalse($vpnUser->active);
        $this->assertTrue(Hash::check('original-password', $vpnUser->password_hash));
    }

    public function test_vpn_users_are_tenant_scoped(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $otherTenant = $this->createTenant('beta-net');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
        VpnUser::factory()->create([
            'tenant_id' => $tenant->id,
            'username' => 'visible_user',
        ]);
        $hiddenVpnUser = VpnUser::factory()->create([
            'tenant_id' => $otherTenant->id,
            'username' => 'hidden_user',
        ]);

        $response = $this->actingAs($user)->get(route('vpn-users.index'));

        $response->assertOk();
        $response->assertSee('visible_user');
        $response->assertDontSee('hidden_user');

        $this->actingAs($user)
            ->get(route('vpn-users.show', $hiddenVpnUser))
            ->assertNotFound();
    }

    private function createTenant(string $slug): Tenant
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
}
