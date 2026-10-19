<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TenantCurrencyDisplayTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('currencies')]
    public function test_admin_pages_use_the_authenticated_tenants_currency(string $currency): void
    {
        $tenant = $this->createTenant('currency-tenant', $currency);
        $otherTenant = $this->createTenant('other-tenant', 'GBP');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        Plan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Local Currency Plan',
            'price' => 19.99,
            'currency' => $currency,
            'status' => 'active',
        ]);
        Plan::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Plan',
            'currency' => 'GBP',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        foreach (['/customers', '/billing/payments', '/billing/credits', '/billing/dashboard', '/billing/invoices'] as $path) {
            $response = $this->get($path)
                ->assertOk()
                ->assertSee('<meta name="tenant-currency" content="'.$currency.'">', false);

            if (in_array($path, ['/billing/reports', '/billing/dashboard'], true)) {
                $response->assertSee($currency.' 200k')
                    ->assertDontSee('$200k');
            }
        }

        app(TenantContext::class)->run($tenant->id, function () use ($currency): void {
            $this->view('billing.reports')
                ->assertSee($currency.' 200k')
                ->assertDontSee('$200k');
        });

        $this->get('/subscriptions/create')
            ->assertOk()
            ->assertSee('<meta name="tenant-currency" content="'.$currency.'">', false)
            ->assertSee('Local Currency Plan - '.$currency.' 19.99')
            ->assertDontSee('Other Tenant Plan')
            ->assertDontSee('$19.99', false)
            ->assertDontSee("'\$' + formatCurrency", false);

        $this->get('/plans/create')
            ->assertOk()
            ->assertSee("currency: '".$currency."'", false);
    }

    public static function currencies(): array
    {
        return [
            'South African rand' => ['ZAR'],
            'euro' => ['EUR'],
            'US dollar' => ['USD'],
        ];
    }

    private function createTenant(string $slug, string $currency): Tenant
    {
        return Tenant::create([
            'id' => (string) str()->uuid(),
            'name' => $slug,
            'slug' => $slug,
            'company_name' => $slug,
            'email' => $slug.'@example.test',
            'timezone' => 'UTC',
            'status' => 'active',
            'currency' => $currency,
        ]);
    }
}
