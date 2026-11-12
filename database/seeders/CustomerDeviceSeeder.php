<?php

namespace Database\Seeders;

use App\Models\AcsConnection;
use App\Models\CustomerDevice;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class CustomerDeviceSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        Tenant::query()->where('acs_enabled', true)->each(function (Tenant $tenant): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant): void {
                $connection = AcsConnection::query()->first();
                if ($connection) {
                    CustomerDevice::factory()->create(['tenant_id' => $tenant->id, 'acs_connection_id' => $connection->id]);
                }
            });
        });
    }
}
