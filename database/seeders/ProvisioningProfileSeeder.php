<?php

namespace Database\Seeders;

use App\Models\ProvisioningProfile;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class ProvisioningProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        Tenant::query()->where('acs_enabled', true)->each(function (Tenant $tenant): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant): void {
                ProvisioningProfile::factory()->create(['tenant_id' => $tenant->id]);
            });
        });
    }
}
