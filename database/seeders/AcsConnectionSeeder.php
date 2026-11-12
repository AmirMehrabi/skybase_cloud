<?php

namespace Database\Seeders;

use App\Models\AcsConnection;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class AcsConnectionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        Tenant::query()->where('acs_enabled', true)->each(function (Tenant $tenant): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant): void {
                AcsConnection::query()->firstOrCreate(['tenant_id' => $tenant->id], ['name' => 'Demo GenieACS', 'base_url' => 'https://acs-'.$tenant->id.'.example.test']);
            });
        });
    }
}
