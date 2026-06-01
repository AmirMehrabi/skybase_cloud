<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsBrandingAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_logo_uses_branding_asset_route_instead_of_public_storage_url(): void
    {
        Storage::fake('public');

        $tenant = $this->createTenant('alpha-net');
        Storage::disk('public')->put($tenant->company_logo = 'settings/'.$tenant->id.'/logo.png', 'logo');
        $tenant->save();

        $url = $tenant->brandingAssetUrl('company_logo');

        $this->assertStringStartsWith(url('/branding-assets/company_logo'), $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_public_storage_settings_logo_url_is_served_by_application_route(): void
    {
        Storage::fake('public');

        $tenant = $this->createTenant('alpha-net');
        $path = 'settings/'.$tenant->id.'/logo.png';

        Storage::disk('public')->put($path, 'logo');

        $response = $this->get('/storage/'.$path);

        $response->assertOk();
        $this->assertSame('logo', $response->streamedContent());
    }

    public function test_admin_can_delete_company_logo_with_plain_post_submission(): void
    {
        Storage::fake('public');

        $tenant = $this->createTenant('alpha-net');
        $path = 'settings/'.$tenant->id.'/logo.png';

        Storage::disk('public')->put($path, 'logo');
        $tenant->forceFill(['company_logo' => $path])->save();

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('settings.delete.asset', ['asset' => 'company_logo']))
            ->assertRedirect(route('settings.index', ['tab' => 'branding']));

        $this->assertNull($tenant->fresh()->company_logo);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_delete_company_logo_with_delete_submission(): void
    {
        Storage::fake('public');

        $tenant = $this->createTenant('alpha-net');
        $path = 'settings/'.$tenant->id.'/logo.png';

        Storage::disk('public')->put($path, 'logo');
        $tenant->forceFill(['company_logo' => $path])->save();

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->delete(route('settings.delete.asset', ['asset' => 'company_logo']))
            ->assertRedirect(route('settings.index', ['tab' => 'branding']));

        $this->assertNull($tenant->fresh()->company_logo);
        Storage::disk('public')->assertMissing($path);
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
