<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GovernmentSso;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GovernmentSsoTest extends TestCase
{
    use RefreshDatabase;

    public static function guards(): array
    {
        return [['web'], ['customer']];
    }

    #[DataProvider('guards')]
    public function test_enrolled_identity_signs_into_the_selected_guard(string $guard): void
    {
        $tenant = Tenant::factory()->create();
        $model = GovernmentSso::model($guard);
        $account = $model::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
            'government_identity_hash' => GovernmentSso::identityHash($tenant->id, $guard, '0123456789'),
        ]);
        $this->configure($tenant, $guard);
        Http::preventStrayRequests();
        Http::fake([
            GovernmentSso::ORIGIN.'/oauth2/token' => Http::response(['access_token' => 'provider-token']),
            GovernmentSso::ORIGIN.'/api/v1/User/userinfo' => Http::response(['nationalId' => '۰۱۲۳۴۵۶۷۸۹']),
        ]);

        $this->withoutVite()->get(route($guard === 'web' ? 'auth.login' : 'customer.login', ['sso_tenant' => $tenant->slug]))
            ->assertOk()->assertSee('Continue with Government SSO');
        $state = $this->begin($tenant, $guard);
        $response = $this->get(route('government-sso.callback', ['state' => $state, 'code' => 'test-code']));

        $response->assertRedirect()->assertSessionMissing('government_sso.pending')->assertSessionMissing('auth.password_confirmed_at');
        $this->assertAuthenticatedAs($account, $guard);
        $this->assertGuest($guard === 'web' ? 'customer' : 'web');
        Http::assertSent(fn ($request): bool => $request->url() === GovernmentSso::ORIGIN.'/oauth2/token'
            && $request['code'] === 'test-code' && filled($request['code_verifier']));
        Http::assertSent(fn ($request): bool => $request->url() === GovernmentSso::ORIGIN.'/api/v1/User/userinfo'
            && $request->hasHeader('Authorization', 'Bearer provider-token'));
    }

    public function test_wrong_state_and_disabling_mid_flow_fail_before_contacting_provider(): void
    {
        $tenant = Tenant::factory()->create();
        $this->configure($tenant, 'web');
        Http::fake();

        $state = $this->begin($tenant, 'web');
        $this->get(route('government-sso.callback', ['state' => 'wrong-state', 'code' => 'test-code']))
            ->assertSessionHasErrors('government_sso');

        $this->configure($tenant, 'customer');
        $this->get(route('government-sso.callback', ['state' => $state, 'code' => 'test-code']))
            ->assertSessionHasErrors('government_sso');
        $this->assertGuest();
        Http::assertNotSent(fn ($request): bool => str_starts_with($request->url(), GovernmentSso::ORIGIN));
    }

    public function test_an_enrollment_from_another_tenant_cannot_be_used(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        Customer::factory()->active()->create([
            'tenant_id' => $otherTenant->id,
            'government_identity_hash' => GovernmentSso::identityHash($otherTenant->id, 'customer', '0123456789'),
        ]);
        $this->configure($tenant, 'customer');
        Http::fake([
            GovernmentSso::ORIGIN.'/oauth2/token' => Http::response(['access_token' => 'provider-token']),
            GovernmentSso::ORIGIN.'/api/v1/User/userinfo' => Http::response(['nationalId' => '0123456789']),
        ]);

        $state = $this->begin($tenant, 'customer');
        $this->get(route('government-sso.callback', ['state' => $state, 'code' => 'test-code']))
            ->assertSessionHasErrors('government_sso');
        $this->assertGuest('customer');
    }

    public function test_admin_can_configure_independent_toggles_and_enroll_or_revoke_staff(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $staff = User::factory()->create(['tenant_id' => $tenant->id]);
        $settings = GovernmentSso::settings($tenant->id);

        $this->actingAs($admin)->withoutVite()->get(route('settings.government-sso'))->assertOk();
        $this->put(route('settings.update.government-sso'), array_replace($settings, [
            'web_enabled' => true,
            'client_id' => 'tenant-client',
            'client_secret' => 'tenant-secret',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $saved = GovernmentSso::settings($tenant->id);
        $this->assertTrue($saved['web_enabled']);
        $this->assertFalse($saved['customer_enabled']);
        $this->assertStringNotContainsString('tenant-secret', DB::table('settings')->where('tenant_id', $tenant->id)->where('key', GovernmentSso::KEY)->value('value'));
        $this->get(route('settings.government-sso'))->assertOk()->assertDontSee('tenant-secret');

        $account = ['account_type' => 'web', 'account_email' => $staff->email];
        $this->post(route('settings.government-sso.identity'), $account + [
            'operation' => 'enroll', 'government_national_id' => '0123456789',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(GovernmentSso::identityHash($tenant->id, 'web', '0123456789'), $staff->fresh()->government_identity_hash);

        $this->post(route('settings.government-sso.identity'), $account + ['operation' => 'revoke'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($staff->fresh()->government_identity_hash);
    }

    public function test_standalone_customer_sso_opens_the_customer_login_when_staff_is_the_default(): void
    {
        config()->set('app.cloud.enabled', false);
        config()->set('app.cloud.guest_entry', 'admin');
        config()->set('app.customer_portal_domain', null);
        $tenant = Tenant::factory()->create();

        $this->withoutVite()->get(route('customer.login'))->assertRedirect(route('auth.login'));

        $this->configure($tenant, 'customer');
        $this->get(route('customer.login'))->assertOk()->assertSee('Continue with Government SSO');
        $this->get(route('auth.login'))->assertOk()->assertDontSee('Continue with Government SSO');
    }

    public function test_registered_entry_starts_on_the_configured_host_and_uses_the_registered_callback(): void
    {
        config()->set('app.cloud.enabled', false);
        config()->set('app.government_sso_public_url', 'https://netuser.example.test');
        $tenant = Tenant::factory()->create();
        $this->configure($tenant, 'customer');

        $registeredEntry = 'https://netuser.example.test/auth/usw/redirect';
        $this->post(route('government-sso.start', 'customer'), ['sso_tenant' => $tenant->slug])
            ->assertRedirect($registeredEntry.'?guard=customer&sso_tenant='.$tenant->slug)
            ->assertSessionMissing('government_sso.pending');

        $response = $this->get($registeredEntry);
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('https://netuser.example.test/auth/usw/callback', $query['redirect_uri']);
        $response->assertSessionHas('government_sso.pending.guard', 'customer');
    }

    private function configure(Tenant $tenant, string $guard): void
    {
        app(TenantContext::class)->run($tenant->id, fn () => Setting::set(GovernmentSso::KEY, [
            $guard.'_enabled' => true,
            'client_id' => 'tenant-client',
            'client_secret' => 'tenant-secret',
            'label' => 'Continue with Government SSO',
            'pkce' => true,
        ], 'json', $tenant->id));
    }

    private function begin(Tenant $tenant, string $guard): string
    {
        $response = $this->post(route('government-sso.start', $guard), ['sso_tenant' => $tenant->slug]);
        $response->assertRedirect();
        $this->assertStringStartsWith(GovernmentSso::ORIGIN.'/oauth2/authorize?', $response->headers->get('Location'));
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);

        return $query['state'];
    }
}
