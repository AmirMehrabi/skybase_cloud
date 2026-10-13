<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\CompatibleSecretCast;
use App\Services\Ldap\LdapConnectionFactory;
use App\Services\OutboundConnectionPolicy;
use App\Services\RouterOs\RouterOsClient;
use App\Services\SafeRouterUsername;
use App\Services\TenantContext;
use App\Services\UploadScanner;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LdapRecord\Container;
use RuntimeException;
use Tests\TestCase;

class SecurityCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_deployments_do_not_enable_disruptive_controls_by_default(): void
    {
        foreach (['encrypt_secrets', 'require_router_tls', 'require_ldap_tls', 'require_mfa', 'upload_scan_required', 'enforce_csp', 'maintain_storage', 'enforce_resource_limits', 'strict_api_access', 'restrict_configured_destinations', 'redact_credentials'] as $control) {
            $this->assertFalse(config('security.'.$control), $control);
        }
        $this->assertSame('public', config('security.attachment_disk'));
        $this->assertFalse(config('session.encrypt'));
        $this->assertFalse((bool) config('session.secure'));
        $this->assertFalse(Schema::hasForeignKey('organizations', ['tenant_id', 'default_plan_id']));
        $this->assertTrue(Schema::hasIndex('customers', ['customer_code'], 'unique'));
    }

    public function test_legacy_secrets_and_json_keep_their_storage_and_response_formats(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'legacy-password', 'coa_secret' => 'legacy-coa']);
        $setting = Setting::query()->create(['tenant_id' => $tenant->id, 'key' => 'ldap.connection', 'value' => ['password' => 'legacy-ldap']]);
        $this->assertSame('legacy-password', $router->getRawOriginal('api_password'));
        $this->assertSame('legacy-password', $router->toArray()['api_password']);
        $this->assertSame(['password' => 'legacy-ldap'], json_decode($setting->getRawOriginal('value'), true));
        $this->assertSame(['password' => 'legacy-ldap'], $setting->fresh()->value);
        $this->assertSame(['password' => 'legacy-ldap'], $setting->toArray()['value']);
    }

    public function test_new_code_reads_both_formats_and_preserves_encrypted_records_during_updates(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'plain']);
        Router::withoutGlobalScopes()->whereKey($router->id)->update(['api_password' => Crypt::encryptString('encrypted')]);
        $router->refresh();
        $this->assertSame('encrypted', $router->api_password);
        $router->update(['api_password' => 'updated']);
        $this->assertTrue(CompatibleSecretCast::isEncrypted($router->getRawOriginal('api_password')));
        $this->assertSame('updated', $router->fresh()->api_password);
    }

    public function test_explicit_encryption_is_repeatable_and_can_be_reversed_before_code_rollback(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'original']);
        $this->artisan('security:encrypt-secrets')->assertFailed();
        config(['security.encrypt_secrets' => true]);
        $this->artisan('security:encrypt-secrets')->assertSuccessful();
        $encrypted = $router->fresh()->getRawOriginal('api_password');
        $this->artisan('security:encrypt-secrets')->assertSuccessful();
        $this->assertSame($encrypted, $router->fresh()->getRawOriginal('api_password'));
        $this->artisan('security:encrypt-secrets', ['--decrypt' => true])->assertFailed();
        config(['security.encrypt_secrets' => false]);
        $this->artisan('security:encrypt-secrets', ['--decrypt' => true])->assertSuccessful();
        $this->assertSame('original', $router->fresh()->getRawOriginal('api_password'));
    }

    public function test_foreign_key_ciphertext_is_not_treated_as_plaintext(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);
        $foreign = new Encrypter(random_bytes(32), 'AES-256-CBC');
        Router::withoutGlobalScopes()->whereKey($router->id)->update(['api_password' => $foreign->encryptString('foreign-secret')]);
        $this->expectException(DecryptException::class);
        $router->fresh()->api_password;
    }

    public function test_legacy_router_ports_and_fallback_are_preserved_without_tls_opt_in(): void
    {
        $client = $this->recordingRouterClient();
        $router = new Router(['tenant_id' => 'test', 'ip_address' => '127.0.0.1', 'api_port' => 8728, 'timeout' => 60]);
        $connection = $client->connect($router);
        fclose($connection);
        $this->assertSame(['tcp://127.0.0.1:8728', 'tcp://127.0.0.1:8729'], $client->addresses);
        $this->assertSame([60, 60], $client->timeouts);
    }

    public function test_strict_router_transport_uses_tls_and_rejects_plaintext(): void
    {
        config(['security.require_router_tls' => true]);
        $client = $this->recordingRouterClient();
        $client->failFirst = false;
        $router = new Router(['tenant_id' => 'test', 'ip_address' => 'router.example', 'api_port' => 8729]);
        $connection = $client->connect($router);
        fclose($connection);
        $this->assertSame(['tls://router.example:8729'], $client->addresses);
        $router->api_port = 8728;
        $this->expectException(RuntimeException::class);
        $client->connect($router);
    }

    public function test_ldap_preserves_hostnames_and_existing_plaintext_configuration(): void
    {
        $factory = app(LdapConnectionFactory::class);
        $name = $factory->register('compat', ['hosts' => ['ldap.internal'], 'use_tls' => false, 'use_starttls' => false]);
        try {
            $configuration = Container::getConnection($name)->getConfiguration();
            $this->assertSame(['ldap.internal'], $configuration->get('hosts'));
            $this->assertSame(389, $configuration->get('port'));
            $this->assertFalse($configuration->get('use_tls'));
            config(['security.require_ldap_tls' => true]);
            $this->expectException(RuntimeException::class);
            $factory->register('strict', ['hosts' => ['ldap.internal']]);
        } finally {
            Container::removeConnection($name);
        }
    }

    public function test_ldaps_defaults_to_636_and_retains_certificate_hostname(): void
    {
        config(['security.require_ldap_tls' => true]);
        $name = app(LdapConnectionFactory::class)->register('ldaps', ['hosts' => ['ldap.internal'], 'use_tls' => true]);
        try {
            $configuration = Container::getConnection($name)->getConfiguration();
            $this->assertSame(636, $configuration->get('port'));
            $this->assertSame(['ldap.internal'], $configuration->get('hosts'));
            $this->assertSame(LDAP_OPT_X_TLS_DEMAND, $configuration->get('options')[LDAP_OPT_X_TLS_REQUIRE_CERT]);
        } finally {
            Container::removeConnection($name);
        }
    }

    public function test_existing_local_destinations_work_until_restrictions_are_enabled(): void
    {
        $policy = new OutboundConnectionPolicy;
        $this->assertSame('127.0.0.1', $policy->resolve('127.0.0.1', 389, configuredDestination: true));
        $this->assertSame('mail.internal', $policy->resolve('mail.internal', 25, configuredDestination: true));
        config(['security.restrict_configured_destinations' => true]);
        $this->expectException(RuntimeException::class);
        $policy->resolve('127.0.0.1', 389, configuredDestination: true);
    }

    public function test_uploads_do_not_require_a_new_scanner_or_global_size_limit_by_default(): void
    {
        config(['security.upload_scanner' => '/missing/scanner']);
        app(UploadScanner::class)->scan(UploadedFile::fake()->create('existing.pdf', 30 * 1024, 'application/pdf'));
        $this->addToAssertionCount(1);
    }

    public function test_browser_policy_is_report_only_until_explicitly_enabled(): void
    {
        $this->get(route('auth.login'))->assertHeader('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Strict-Transport-Security')->assertHeaderMissing('X-Frame-Options');
    }

    public function test_legacy_route_mapping_works_without_disabling_tenant_scopes(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        Route::middleware(['web', 'auth', 'permission'])->get('/legacy-route', fn (): string => 'legacy')->name('legacy.route');
        $this->actingAs($user)->get('/legacy-route')->assertOk();
        config(['security.strict_route_permissions' => true]);
        $this->get('/legacy-route')->assertForbidden();
    }

    public function test_legacy_username_characters_remain_valid_and_command_escaping_stays_enabled(): void
    {
        $validator = validator(['username' => 'customer with space/$'], ['username' => SafeRouterUsername::validationRule()]);
        $this->assertTrue($validator->passes());
        $this->assertSame('"customer with space/\\$"', SafeRouterUsername::quote('customer with space/$'));
        config(['security.strict_router_usernames' => true]);
        $this->assertTrue(validator(['username' => 'customer with space/$'], ['username' => SafeRouterUsername::validationRule()])->fails());
    }

    public function test_tenant_constraint_upgrade_checks_all_data_before_installing_constraints(): void
    {
        $tenant = $this->tenant();
        $other = $this->tenant();
        $plan = Plan::factory()->create(['tenant_id' => $other->id]);
        Organization::factory()->create(['tenant_id' => $tenant->id, 'default_plan_id' => $plan->id]);
        $this->artisan('security:tenant-constraints')->assertFailed();
        $this->assertFalse(Schema::hasForeignKey('organizations', ['tenant_id', 'default_plan_id']));
    }

    public function test_valid_tenant_constraints_are_installed_repeatably_and_removable(): void
    {
        $this->artisan('security:tenant-constraints')->assertSuccessful();
        $this->artisan('security:tenant-constraints')->assertSuccessful();
        $this->assertTrue(Schema::hasForeignKey('organizations', ['tenant_id', 'default_plan_id']));
        $this->artisan('security:tenant-constraints', ['--remove' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasForeignKey('organizations', ['tenant_id', 'default_plan_id']));
    }

    public function test_rows_written_by_old_instances_inherit_the_verified_parent_tenant(): void
    {
        $tenant = $this->tenant();
        $other = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $setting = UserSetting::query()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'key' => 'theme', 'value' => 'dark']);
        UserSetting::withoutGlobalScopes()->whereKey($setting->id)->update(['tenant_id' => null]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'invoice_number' => 'COMPAT-1',
            'billing_period_start' => now(), 'billing_period_end' => now()->addMonth(),
            'issue_date' => now(), 'due_date' => now()->addWeek(),
        ]);
        $item = $invoice->items()->create(['tenant_id' => $tenant->id, 'description' => 'Legacy service', 'quantity' => 1, 'unit_price' => 10]);
        InvoiceItem::withoutGlobalScopes()->whereKey($item->id)->update(['tenant_id' => null]);
        $this->assertSame('dark', $user->getSetting('theme'));
        $user->setSetting('theme', 'light');
        $this->assertSame('light', $user->getSetting('theme'));
        $this->assertSame(1, $user->settings()->count());
        $this->assertSame(1, $invoice->items()->count());
        app(TenantContext::class)->run($other->id, function () use ($invoice, $user): void {
            $this->assertSame(0, InvoiceItem::query()->count());
            $this->assertSame(0, UserSetting::query()->count());
            $this->assertSame(0, $invoice->items()->count());
            $this->assertNull($user->getSetting('theme'));
        });
    }

    public function test_central_admin_preferences_remain_available_without_tenant_context(): void
    {
        $central = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin']);
        $central->setSetting('theme', 'dark');
        $this->assertSame('dark', $central->getSetting('theme'));
    }

    public function test_legacy_subscription_response_keeps_credentials_until_redaction_opt_in(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->actingAs($user);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id,
            'status' => 'active', 'pppoe_username' => 'legacy customer', 'pppoe_password' => 'legacy-secret',
        ]);
        $this->getJson(route('subscriptions.data'))->assertOk()->assertJsonPath('subscriptions.0.pppoe_password', 'legacy-secret');
        config(['security.redact_credentials' => true]);
        $this->getJson(route('subscriptions.data'))->assertOk()->assertJsonMissingPath('subscriptions.0.pppoe_password');
    }

    public function test_compatibility_preflight_accepts_legacy_data_but_reports_wrong_encryption_keys(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'legacy-secret']);
        $this->artisan('security:preflight', ['--compatibility' => true])->assertSuccessful();
        $foreign = new Encrypter(random_bytes(32), 'AES-256-CBC');
        Router::withoutGlobalScopes()->whereKey($router->id)->update(['api_password' => $foreign->encryptString('foreign-secret')]);
        $this->artisan('security:preflight', ['--compatibility' => true])->expectsOutput('FAIL routers secret readability')->assertFailed();
    }

    private function recordingRouterClient(): RouterOsClient
    {
        return new class extends RouterOsClient
        {
            public array $addresses = [];

            public array $timeouts = [];

            public bool $failFirst = true;

            protected function openConnection(string $address, int $timeout, mixed $context): mixed
            {
                $this->addresses[] = $address;
                $this->timeouts[] = $timeout;

                return $this->failFirst && count($this->addresses) === 1 ? false : fopen('php://temp', 'r+');
            }
        };
    }

    private function tenant(): Tenant
    {
        $slug = Str::random(12);

        return Tenant::query()->create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug, 'company_name' => $slug, 'email' => $slug.'@example.com', 'status' => 'active']);
    }
}
