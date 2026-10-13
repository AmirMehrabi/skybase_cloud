<?php

namespace Tests\Feature;

use App\Jobs\ImportExport\ProcessExportJob;
use App\Jobs\ImportExport\ProcessImportJob;
use App\Models\Customer;
use App\Models\ImportExportRun;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\TotpService;
use App\Support\ImportExport\ImportExportSchema;
use App\Support\ImportExport\SpreadsheetImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'security.enforce_rate_limits' => true,
            'security.encrypt_secrets' => true,
            'security.redact_credentials' => true,
            'security.enforce_active_accounts' => true,
            'security.strict_route_permissions' => true,
            'security.enforce_csp' => true,
            'security.hsts' => true,
        ]);
    }

    public function test_tenant_users_cannot_manage_central_tenants(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        $this->actingAs($user);
        $this->get(route('admin.super-admin.tenants.index'))->assertForbidden();
        $this->get(route('admin.super-admin.tenants.show', $tenant))->assertForbidden();
        $this->put(route('admin.super-admin.tenants.update', $tenant), [])->assertForbidden();
        $this->post(route('admin.super-admin.tenants.suspend', $tenant))->assertForbidden();
        $this->delete(route('admin.super-admin.tenants.destroy', $tenant))->assertForbidden();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'active']);
    }

    public function test_horizon_is_reserved_for_central_admins(): void
    {
        $tenant = $this->tenant();
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        $central = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin', 'status' => 'active']);
        $this->assertFalse(Gate::forUser($owner)->allows('viewHorizon'));
        $this->assertTrue(Gate::forUser($central)->allows('viewHorizon'));
    }

    public function test_disabled_accounts_cannot_login_or_retain_access(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'inactive', 'password' => 'test-password']);
        $this->post(route('auth.login.store'), ['email' => $user->email, 'password' => 'test-password'])->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password');
        $this->assertGuest('web');
        $this->actingAs($user)->get(route('profile.index'))->assertForbidden();
    }

    public function test_login_attempts_are_rate_limited_and_never_flash_passwords(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('auth.login.store'), ['email' => 'missing@example.com', 'password' => 'wrong'])->assertSessionMissing('_old_input.password');
        }
        $this->post(route('auth.login.store'), ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_queries_fail_closed_without_context_and_cannot_escape_tenant_filters(): void
    {
        $tenant = $this->tenant();
        $other = $this->tenant();
        Plan::factory()->create(['tenant_id' => $tenant->id]);
        Plan::factory()->create(['tenant_id' => $other->id]);
        $this->assertSame(0, Plan::query()->count());
        $this->assertSame(1, Plan::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, Plan::query()->where('tenant_id', $tenant->id)->orWhere('tenant_id', $other->id)->count());
        app(TenantContext::class)->run($tenant->id, function () use ($tenant, $other): void {
            $this->assertSame($tenant->id, tenant()->id);
            $this->assertSame(1, Plan::query()->count());
            $this->assertSame(0, Plan::query()->where('tenant_id', $other->id)->count());
        });
        $this->assertNull(tenant());
        $this->assertNull(app(TenantContext::class)->id());
    }

    public function test_background_plan_exports_only_include_the_run_tenant(): void
    {
        Storage::fake('imports');
        $tenant = $this->tenant();
        $other = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        Plan::factory()->create(['tenant_id' => $tenant->id, 'name' => '=1+1']);
        Plan::factory()->create(['tenant_id' => $other->id, 'name' => 'Other Tenant']);
        $run = $this->createRun($tenant, $user, 'export');
        Auth::forgetGuards();
        (new ProcessExportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $run->refresh();
        $sheet = IOFactory::load(Storage::disk('imports')->path($run->file_path))->getActiveSheet();
        $this->assertSame(2, $sheet->getHighestDataRow());
        $this->assertSame('=1+1', $sheet->getCell('A2')->getValue());
        $this->assertSame('s', $sheet->getCell('A2')->getDataType());
        $this->assertNull(app(TenantContext::class)->id());
        $this->assertFalse(Auth::guard('web')->hasUser());
    }

    public function test_background_plan_import_does_not_overwrite_another_tenants_plan(): void
    {
        Storage::fake('imports');
        $tenant = $this->tenant();
        $other = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        $plan = Plan::factory()->create(['tenant_id' => $other->id, 'name' => 'Untouched', 'internal_name' => 'same_name']);
        $run = $this->createRun($tenant, $user, 'import');
        $path = 'test/import.xlsx';
        Storage::disk('imports')->makeDirectory('test');
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([['name', 'internal_name', 'status', 'type', 'download_speed', 'upload_speed'], ['Imported', 'same_name', 'active', 'pppoe', 100, 100]]);
        (new Xlsx($spreadsheet))->save(Storage::disk('imports')->path($path));
        $run->update(['file_path' => $path]);
        Auth::forgetGuards();
        (new ProcessImportJob($run->id))->handle(app(SpreadsheetImportExportService::class));
        $this->assertSame('Untouched', $plan->fresh()->name);
        $this->assertDatabaseHas('plans', ['tenant_id' => $tenant->id, 'name' => 'Imported']);
        $this->assertNull(app(TenantContext::class)->id());
    }

    public function test_integration_secrets_are_encrypted_and_hidden(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'router-secret', 'coa_secret' => 'coa-secret']);
        $this->assertNotSame('router-secret', $router->getRawOriginal('api_password'));
        $this->assertSame('router-secret', $router->api_password);
        $this->assertArrayNotHasKey('api_password', $router->toArray());
        $setting = Setting::query()->create(['tenant_id' => $tenant->id, 'key' => 'ldap.connection', 'value' => ['password' => 'ldap-secret']]);
        $this->assertStringNotContainsString('ldap-secret', $setting->getRawOriginal('value'));
        $this->assertSame('ldap-secret', $setting->value['password']);
    }

    public function test_privileged_users_must_enroll_in_mfa_and_codes_cannot_be_replayed(): void
    {
        config(['security.require_mfa' => true]);
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active', 'password' => 'test-password']);
        $this->actingAs($user)->get(route('profile.index'))->assertRedirect(route('mfa.setup'));
        $this->get(route('mfa.setup'))->assertOk();
        $secret = Crypt::decryptString(session('mfa_pending_secret'));
        $code = app(TotpService::class)->code($secret, intdiv(now()->timestamp, 30));
        $this->post(route('mfa.confirm'), ['code' => $code, 'current_password' => 'test-password'])->assertOk();
        $this->assertNotNull($user->fresh()->mfa_confirmed_at);
        $this->post(route('mfa.verify'), ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_recovery_codes_are_consumed_once_for_central_admins(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null, 'role' => 'super_admin', 'status' => 'active',
            'mfa_secret' => app(TotpService::class)->secret(), 'mfa_confirmed_at' => now(),
            'mfa_recovery_codes' => [hash('sha256', 'one-use-code')],
        ]);
        $this->actingAs($user)->post(route('mfa.verify'), ['recovery_code' => 'one-use-code'])->assertRedirect();
        $this->assertSame([], $user->fresh()->mfa_recovery_codes);
        $this->post(route('mfa.verify'), ['recovery_code' => 'one-use-code'])->assertSessionHasErrors('code');
    }

    public function test_revealing_subscription_credentials_requires_current_password(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'password' => 'test-password']);
        $subscription = app(TenantContext::class)->run($tenant->id, function () use ($tenant): Subscription {
            $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);

            return Subscription::query()->create([
                'tenant_id' => $tenant->id, 'customer_id' => $customer->id,
                'status' => 'active', 'pppoe_username' => 'security.test', 'pppoe_password' => 'protected-secret',
            ]);
        });
        $this->actingAs($user)->postJson(route('subscriptions.credentials', $subscription), ['current_password' => 'incorrect'])->assertUnprocessable()->assertJsonMissing(['password' => 'protected-secret']);
        $this->postJson(route('subscriptions.credentials', $subscription), ['current_password' => 'test-password'])->assertOk()->assertJsonPath('password', 'protected-secret')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_unmapped_protected_routes_are_denied(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        Route::middleware(['web', 'auth', 'permission'])->get('/security-unmapped', fn (): string => 'unmapped')->name('security.unmapped');
        $this->actingAs($user)->get('/security-unmapped')->assertForbidden();
    }

    public function test_security_headers_are_set_on_https_responses(): void
    {
        $this->get('https://localhost/auth/login')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Strict-Transport-Security', 'max-age=31536000')->assertHeader('Content-Security-Policy');
    }

    private function tenant(): Tenant
    {
        $slug = Str::random(12);

        return Tenant::query()->create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug, 'company_name' => $slug, 'email' => $slug.'@example.com', 'status' => 'active']);
    }

    private function createRun(Tenant $tenant, User $user, string $direction): ImportExportRun
    {
        return ImportExportRun::query()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'module' => ImportExportSchema::MODULE_PLANS, 'direction' => $direction, 'status' => 'queued', 'disk' => 'imports', 'filters' => []]);
    }
}
