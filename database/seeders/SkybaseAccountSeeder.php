<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\SkybaseBillingService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class SkybaseAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('skybase_billing.enabled')) {
            return;
        }
        Tenant::query()->each(fn (Tenant $tenant) => app(TenantContext::class)->run($tenant->id, fn () => app(SkybaseBillingService::class)->account($tenant->id)));
    }
}
