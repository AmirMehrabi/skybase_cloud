<?php

namespace Database\Seeders;

use App\Models\CustomerDevice;
use App\Models\DeviceAction;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

class DeviceActionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        Tenant::query()->where('acs_enabled', true)->each(function (Tenant $tenant): void {
            app(TenantContext::class)->run($tenant->id, function () use ($tenant): void {
                $device = CustomerDevice::query()->first();
                if ($device) {
                    DeviceAction::factory()->create(['tenant_id' => $tenant->id, 'customer_device_id' => $device->id, 'status' => 'completed', 'payload' => null, 'message' => 'Demo inventory refresh', 'finished_at' => now()]);
                }
            });
        });
    }
}
