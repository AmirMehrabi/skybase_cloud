<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant): void {
                Campaign::factory()->create(['tenant_id' => $tenant->id]);
            });
        });
    }
}
