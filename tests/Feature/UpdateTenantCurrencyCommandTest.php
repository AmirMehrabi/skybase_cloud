<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTenantCurrencyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_currency_changes_without_writing_them_during_a_dry_run(): void
    {
        [$tenant, $plan] = $this->currencyRecords();

        $this->artisan('billing:update-currency', [
            'currency' => 'ZAR',
            '--tenant-id' => $tenant->id,
            '--dry-run' => true,
        ])->expectsOutput('Dry run complete. No records were changed.')->assertSuccessful();

        $this->assertSame('USD', $tenant->fresh()->currency);
        $this->assertSame('USD', $plan->fresh()->currency);
    }

    public function test_it_updates_only_the_specified_tenants_currency_codes_without_converting_amounts(): void
    {
        [$tenant, $plan] = $this->currencyRecords();
        $otherTenant = $this->tenant('Other tenant');
        $otherPlan = Plan::factory()->create(['tenant_id' => $otherTenant->id, 'currency' => 'USD', 'price' => 125.50]);

        $this->artisan('billing:update-currency', [
            'currency' => 'zar',
            '--tenant-id' => $tenant->id,
            '--force' => true,
        ])->expectsOutput('Currency codes updated to ZAR.')->assertSuccessful();

        $this->assertSame('ZAR', $tenant->fresh()->currency);
        $this->assertSame('ZAR', $plan->fresh()->currency);
        $this->assertSame('125.50', $plan->fresh()->price);
        $this->assertSame('ZAR', (string) Setting::query()->where('tenant_id', $tenant->id)->where('key', 'currency')->value('value'));
        $this->assertSame('USD', $otherPlan->fresh()->currency);
    }

    /** @return array{Tenant, Plan} */
    private function currencyRecords(): array
    {
        $tenant = $this->tenant('Currency tenant');
        $plan = Plan::factory()->create(['tenant_id' => $tenant->id, 'currency' => 'USD', 'price' => 125.50]);
        Setting::query()->create([
            'tenant_id' => $tenant->id,
            'key' => 'currency',
            'value' => 'USD',
            'type' => 'string',
            'group' => 'billing',
        ]);

        return [$tenant, $plan];
    }

    private function tenant(string $name): Tenant
    {
        return Tenant::create([
            'id' => (string) str()->uuid(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'company_name' => $name,
            'email' => str($name)->slug().'@example.test',
            'timezone' => 'UTC',
            'status' => 'active',
            'currency' => 'USD',
        ]);
    }
}
