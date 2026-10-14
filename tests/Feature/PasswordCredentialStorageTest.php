<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ImportExportRun;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CredentialVisibility;
use App\Services\TenantContext;
use App\Support\ImportExport\ImportExportSchema;
use App\Support\ImportExport\SpreadsheetImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PasswordCredentialStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.password_encryption' => false, 'security.redact_credentials' => false]);
    }

    public function test_new_setting_defaults_to_false_and_respects_existing_redaction(): void
    {
        $defaults = require config_path('security.php');
        $this->assertFalse($defaults['password_encryption']);
        $this->assertFalse(CredentialVisibility::passwordsRedacted());
        config(['security.redact_credentials' => true]);
        $this->assertTrue(CredentialVisibility::passwordsRedacted());
    }

    public function test_both_modes_store_and_synchronize_hidden_hashes_without_changing_plain_credentials(): void
    {
        $tenant = $this->tenant();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));

        foreach ([false, true] as $enabled) {
            config(['security.password_encryption' => $enabled]);
            $subscription = $this->subscription($tenant);
            $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'router-secret']);

            foreach ([$subscription, $router] as $model) {
                $column = $model instanceof Subscription ? 'pppoe_password' : 'api_password';
                $hashColumn = $column.'_hash';
                $original = $model->getAttribute($column);
                $hash = $model->getAttribute($hashColumn);
                $this->assertTrue(Hash::driver('argon2id')->check($original, $hash));
                $this->assertSame($original, $model->getRawOriginal($column));
                $this->assertArrayNotHasKey($hashColumn, $model->toArray());
                $this->assertSame(! $enabled, array_key_exists($column, $model->toArray()));

                $model->update(['name' => 'Renamed']);
                $this->assertSame($hash, $model->fresh()->getAttribute($hashColumn));
                $model->update([$column => $original]);
                $this->assertSame($hash, $model->fresh()->getAttribute($hashColumn));
                $model->update([$column => str_repeat('new-secret-', 20)]);
                $model->refresh();
                $this->assertTrue(Hash::driver('argon2id')->check(str_repeat('new-secret-', 20), $model->getAttribute($hashColumn)));
                $this->assertNotSame($hash, $model->getAttribute($hashColumn));
                $model->update([$column => null]);
                $this->assertNull($model->fresh()->getAttribute($hashColumn));
            }
        }
    }

    public function test_credentials_are_hashed_even_when_provisioning_disables_model_events(): void
    {
        $tenant = $this->tenant();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));
        $subscription = Subscription::withoutEvents(fn () => $this->subscription($tenant));
        $router = Router::withoutEvents(fn () => Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'router-secret']));
        foreach ([$subscription, $router] as $model) {
            $column = $model instanceof Subscription ? 'pppoe_password' : 'api_password';
            $this->assertTrue(Hash::driver('argon2id')->check($model->getAttribute($column), $model->getAttribute($column.'_hash')));
            $model->forceFill([$column => 'quiet-update'])->saveQuietly();
            $this->assertTrue(Hash::driver('argon2id')->check('quiet-update', $model->fresh()->getAttribute($column.'_hash')));
        }
    }

    public function test_encrypted_original_credentials_remain_readable_and_hashes_stay_stable(): void
    {
        config(['security.encrypt_secrets' => true]);
        $tenant = $this->tenant();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));
        foreach ([$this->subscription($tenant), Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'router-secret'])] as $model) {
            $column = $model instanceof Subscription ? 'pppoe_password' : 'api_password';
            $password = $model->getAttribute($column);
            $hash = $model->getAttribute($column.'_hash');
            $this->assertNotSame($password, $model->getRawOriginal($column));
            $this->assertTrue(Hash::driver('argon2id')->check($password, $hash));
            $model->update([$column => $password]);
            $this->assertSame($hash, $model->fresh()->getAttribute($column.'_hash'));
            config(['security.password_encryption' => true]);
            $this->assertSame($password, $model->fresh()->getAttribute($column));
        }
    }

    public function test_pages_and_responses_redact_passwords_and_reveal_is_forbidden_only_when_enabled(): void
    {
        $tenant = $this->tenant();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'password' => 'test-password']));
        $subscription = $this->subscription($tenant);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'router-secret']);
        $this->getJson(route('subscriptions.data'))->assertOk()->assertJsonPath('subscriptions.0.pppoe_password', 'subscription-secret');
        $this->postJson(route('subscriptions.credentials', $subscription), ['current_password' => 'test-password'])
            ->assertOk()->assertJsonPath('password', 'subscription-secret');

        config(['security.password_encryption' => true]);
        $this->getJson(route('subscriptions.data'))->assertOk()->assertJsonMissingPath('subscriptions.0.pppoe_password');
        $this->postJson(route('subscriptions.credentials', $subscription), ['current_password' => 'test-password'])->assertForbidden();
        foreach ([route('subscriptions.show', $subscription), route('subscriptions.edit', $subscription), route('subscriptions.index'), route('customers.show', $subscription->customer), route('routers.edit', $router)] as $url) {
            $this->get($url)->assertOk()->assertDontSee('subscription-secret')->assertDontSee('router-secret')
                ->assertDontSee($subscription->pppoe_password_hash)->assertDontSee($router->api_password_hash);
        }

        config(['security.password_encryption' => false]);
        $this->get(route('customers.show', $subscription->customer))->assertOk()->assertSee('subscription-secret');
        $this->get(route('subscriptions.edit', $subscription))->assertOk()->assertSee('subscription-secret');
    }

    public function test_router_create_responses_and_blank_password_updates_preserve_compatibility(): void
    {
        $tenant = $this->tenant();
        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']));
        foreach ([false, true] as $enabled) {
            config(['security.password_encryption' => $enabled]);
            $data = [
                'name' => 'Test router', 'vendor' => 'Mikrotik',
                'ip_address' => $enabled ? '192.0.2.11' : '192.0.2.10',
                'api_port' => 8728, 'ssh_port' => 22,
                'api_username' => 'admin', 'api_password' => 'router-secret',
            ];
            $response = $this->postJson(route('routers.store'), $data)->assertCreated()->assertJsonMissingPath('router.api_password_hash');
            if ($enabled) {
                $response->assertJsonMissingPath('router.api_password');
            } else {
                $response->assertJsonPath('router.api_password', 'router-secret');
            }
            $router = Router::query()->findOrFail($response->json('router.id'));
            $hash = $router->api_password_hash;
            $this->putJson(route('routers.update', $router), [...$data, 'api_password' => ''])->assertOk();
            $this->assertSame('router-secret', $router->fresh()->api_password);
            $this->assertSame($hash, $router->fresh()->api_password_hash);

            $subscription = $this->subscription($tenant);
            $hash = $subscription->pppoe_password_hash;
            $this->putJson(route('subscriptions.update', $subscription), ['pppoe_password' => ''])->assertOk();
            $this->assertSame('subscription-secret', $subscription->fresh()->pppoe_password);
            $this->assertSame($hash, $subscription->fresh()->pppoe_password_hash);
        }
    }

    public function test_cross_tenant_credentials_cannot_be_revealed_in_either_mode(): void
    {
        $tenant = $this->tenant();
        $subscription = app(TenantContext::class)->run($tenant->id, fn () => $this->subscription($tenant));
        $otherTenant = $this->tenant();
        $this->actingAs(User::factory()->create(['tenant_id' => $otherTenant->id, 'role' => 'owner', 'password' => 'test-password']));
        foreach ([false, true] as $enabled) {
            config(['security.password_encryption' => $enabled]);
            $this->postJson(route('subscriptions.credentials', $subscription), ['current_password' => 'test-password'])->assertNotFound();
        }
    }

    public function test_backfill_is_tenant_scoped_idempotent_and_preserves_original_storage(): void
    {
        $tenant = $this->tenant();
        $otherTenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'api_password' => 'router-secret']);
        $otherRouter = Router::factory()->create(['tenant_id' => $otherTenant->id, 'api_password' => 'other-secret']);
        $subscription = app(TenantContext::class)->run($tenant->id, fn () => $this->subscription($tenant));
        foreach ([$router, $otherRouter, $subscription] as $model) {
            $column = $model instanceof Subscription ? 'pppoe_password_hash' : 'api_password_hash';
            $model::query()->where('tenant_id', $model->tenant_id)->whereKey($model->getKey())->toBase()->update([$column => null]);
        }
        $this->artisan('security:backfill-credential-hashes', ['--tenant' => $tenant->id])->expectsOutput('Backfilled 2 credential hashes.')->assertSuccessful();
        $hash = Router::query()->where('tenant_id', $tenant->id)->findOrFail($router->id)->api_password_hash;
        $this->assertTrue(Hash::driver('argon2id')->check('router-secret', $hash));
        $this->assertNull(Router::query()->where('tenant_id', $otherTenant->id)->findOrFail($otherRouter->id)->api_password_hash);
        $freshSubscription = Subscription::query()->where('tenant_id', $tenant->id)->findOrFail($subscription->id);
        $this->assertTrue(Hash::driver('argon2id')->check('subscription-secret', $freshSubscription->pppoe_password_hash));
        $this->assertSame('subscription-secret', $freshSubscription->getRawOriginal('pppoe_password'));
        $this->artisan('security:backfill-credential-hashes', ['--tenant' => $tenant->id])->expectsOutput('Backfilled 0 credential hashes.')->assertSuccessful();
        $this->assertSame($hash, Router::query()->where('tenant_id', $tenant->id)->findOrFail($router->id)->api_password_hash);
        $this->artisan('security:backfill-credential-hashes', ['--tenant' => 'missing'])->assertFailed();
    }

    public function test_subscription_exports_omit_passwords_only_when_redacted(): void
    {
        Storage::fake('imports');
        $tenant = $this->tenant();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
        $this->actingAs($user);
        $this->subscription($tenant);
        foreach ([false, true] as $enabled) {
            config(['security.password_encryption' => $enabled]);
            $run = ImportExportRun::query()->create([
                'tenant_id' => $tenant->id, 'user_id' => $user->id,
                'module' => ImportExportSchema::MODULE_SUBSCRIPTIONS,
                'direction' => ImportExportRun::DIRECTION_EXPORT,
                'status' => ImportExportRun::STATUS_QUEUED, 'disk' => 'imports',
            ]);
            app(SpreadsheetImportExportService::class)->export($run);
            $sheet = IOFactory::load(Storage::disk('imports')->path($run->file_path))->getActiveSheet();
            $rows = $sheet->toArray();
            $column = array_search('pppoe_password', $rows[0], true);
            $this->assertNotFalse($column);
            $this->assertSame($enabled ? null : 'subscription-secret', $rows[1][$column]);
        }
    }

    private function subscription(Tenant $tenant): Subscription
    {
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);

        return Subscription::query()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::random(12),
            'plan_id' => Plan::factory()->create(['tenant_id' => $tenant->id])->id,
            'status' => 'active', 'pppoe_username' => 'user.'.Str::random(8),
            'pppoe_password' => 'subscription-secret',
        ]);
    }

    private function tenant(): Tenant
    {
        $slug = Str::random(12);

        return Tenant::query()->create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug, 'company_name' => $slug, 'email' => $slug.'@example.com', 'status' => 'active']);
    }
}
