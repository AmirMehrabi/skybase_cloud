<?php

namespace Tests\Feature;

use App\Models\ImportExportRun;
use App\Models\ImportExportRunRow;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SensitiveStorageUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_plaintext_secrets_are_preserved_until_explicit_upgrade(): void
    {
        $tenant = $this->tenant();
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);
        $setting = Setting::query()->create(['tenant_id' => $tenant->id, 'key' => 'ldap.connection', 'value' => ['password' => 'legacy-secret']]);
        $migration = require base_path('database/migrations/2026_10_12_203327_harden_sensitive_storage_and_add_multi_factor_fields.php');
        $constraints = require base_path('database/migrations/2026_10_12_205741_enforce_tenant_consistency_for_billing_references.php');
        $constraints->down();
        $migration->down();
        Router::withoutGlobalScopes()->whereKey($router->id)->update(['api_password' => 'legacy-router-secret']);
        $migration->up();
        $migration->up();
        $constraints->up();
        $constraints->up();
        $this->assertSame('legacy-router-secret', $router->fresh()->getRawOriginal('api_password'));
        config(['security.encrypt_secrets' => true]);
        $this->artisan('security:encrypt-secrets')->assertSuccessful();
        $this->artisan('security:encrypt-secrets')->assertSuccessful();
        $this->assertSame('legacy-router-secret', $router->fresh()->api_password);
        $this->assertNotSame('legacy-router-secret', $router->fresh()->getRawOriginal('api_password'));
        $this->assertSame('legacy-secret', $setting->fresh()->value['password']);
    }

    public function test_existing_attachments_move_privately_and_import_payloads_are_redacted(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $tenant = $this->tenant();
        $attachment = app(TenantContext::class)->run($tenant->id, fn (): TicketAttachment => TicketAttachment::factory()->create(['visibility' => 'internal', 'disk' => 'public']));
        Storage::disk('public')->put($attachment->path, 'private diagnostic');
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $run = ImportExportRun::query()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'module' => 'subscriptions', 'direction' => 'import', 'status' => 'completed', 'disk' => 'imports']);
        $row = ImportExportRunRow::query()->create(['tenant_id' => $tenant->id, 'import_export_run_id' => $run->id, 'row_number' => 1, 'status' => 'created', 'payload' => ['name' => 'Customer', 'pppoe_password' => 'secret']]);
        $this->artisan('security:harden-storage')->assertSuccessful();
        Storage::disk('public')->assertMissing($attachment->path);
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame('local', $attachment->fresh()->disk);
        $this->assertSame(['name' => 'Customer'], $row->fresh()->payload);
        $this->artisan('security:harden-storage')->assertSuccessful();
    }

    private function tenant(): Tenant
    {
        $slug = Str::random(12);

        return Tenant::query()->create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug, 'company_name' => $slug, 'email' => $slug.'@example.com', 'status' => 'active']);
    }
}
