<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsoleHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['skybase_billing.console_host_enabled' => true, 'skybase_billing.console_host' => 'console.skybase.app', 'skybase_billing.owner_console_enabled' => true]);
    }

    public function test_console_has_dedicated_login_and_host_only_session(): void
    {
        $response = $this->get('https://console.skybase.app/auth/login');
        $response->assertOk()->assertSee('Sign in to your console');
        $this->assertSame('skybase_console_session', config('session.cookie'));
        $this->assertNull(config('session.domain'));
        $this->get('https://console.skybase.app/customers')->assertNotFound();
    }

    public function test_main_site_redirects_old_console_entry(): void
    {
        $this->get('https://skybase.app/admin/super-admin/tenants')->assertRedirect('https://console.skybase.app/admin/super-admin/tenants');
    }

    public function test_tenant_credentials_cannot_authenticate_to_console(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->post('https://console.skybase.app/auth/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_central_credentials_are_rejected_by_normal_login(): void
    {
        $user = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin']);
        $this->post('https://skybase.app/auth/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_central_credentials_work_on_console_and_require_mfa(): void
    {
        $user = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin']);
        $this->post('https://console.skybase.app/auth/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->get('https://console.skybase.app/admin/super-admin/tenants')->assertRedirect(route('mfa.setup'));
        $this->get('https://console.skybase.app/account/security/setup')->assertOk()->assertSee('Protect your account');
    }
}
