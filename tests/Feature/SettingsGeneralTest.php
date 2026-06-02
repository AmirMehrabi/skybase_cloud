<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsGeneralTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_settings_page_includes_sierra_leone_currency_and_timezone_options(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = $this->createAdminForTenant($tenant);

        $response = $this->actingAs($admin)->get(route('settings.index', ['tab' => 'general']));

        $response->assertOk();
        $response->assertSee('SLE - Sierra Leonean Leone (Le)');
        $response->assertSee('Africa/Freetown');
    }

    public function test_admin_can_save_general_settings_without_validation_errors(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = $this->createAdminForTenant($tenant);

        $response = $this->actingAs($admin)->put(route('settings.update.general'), [
            'company_name' => 'Alpha ISP',
            'tagline' => 'Connectivity for everyone',
            'business_license' => 'LIC-123456',
            'tax_id' => 'TIN-987654',
            'website_url' => 'https://alpha.test',
            'support_phone' => '+232 76 123456',
            'support_email' => 'support@alpha.test',
            'address' => '1 Main Street',
            'city' => 'Freetown',
            'state' => 'Western Area',
            'zip' => '00100',
            'country' => 'Sierra Leone',
            'timezone' => 'Africa/Freetown',
            'date_format' => 'd/m/Y',
            'time_format' => '24h',
            'first_day_of_week' => 'monday',
            'currency' => 'SLE',
            'currency_symbol_position' => 'before',
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'locale' => 'en',
            'maintenance_mode' => '1',
            'custom_domain' => 'settings.alpha.test',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'general']));
        $response->assertSessionHasNoErrors();

        $updatedTenant = $tenant->fresh();

        $this->assertSame('Alpha ISP', $updatedTenant->company_name);
        $this->assertSame('Africa/Freetown', $updatedTenant->timezone);
        $this->assertSame('24h', $updatedTenant->time_format);
        $this->assertSame('monday', $updatedTenant->first_day_of_week);
        $this->assertSame('SLE', $updatedTenant->currency);
        $this->assertSame('en', $updatedTenant->locale);
        $this->assertTrue($updatedTenant->maintenance_mode);
        $this->assertSame('settings.alpha.test', $updatedTenant->custom_domain);
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

    private function createAdminForTenant(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }
}
