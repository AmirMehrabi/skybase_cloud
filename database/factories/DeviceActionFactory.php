<?php

namespace Database\Factories;

use App\Models\CustomerDevice;
use App\Models\DeviceAction;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeviceAction> */
class DeviceActionFactory extends Factory
{
    protected $model = DeviceAction::class;

    public function definition(): array
    {
        return ['tenant_id' => tenant_id() ?? Tenant::factory(), 'customer_device_id' => CustomerDevice::factory(), 'operation' => 'refresh', 'payload' => ['name' => 'refreshObject', 'objectName' => '']];
    }
}
