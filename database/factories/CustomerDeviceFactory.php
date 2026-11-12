<?php

namespace Database\Factories;

use App\Models\AcsConnection;
use App\Models\CustomerDevice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerDevice> */
class CustomerDeviceFactory extends Factory
{
    protected $model = CustomerDevice::class;

    public function definition(): array
    {
        return ['tenant_id' => tenant_id() ?? Tenant::factory(), 'acs_connection_id' => AcsConnection::factory(), 'external_id' => 'MikroTik-hAP-'.fake()->unique()->numerify('########'), 'manufacturer' => 'MikroTik', 'model' => 'hAP lite', 'serial_number' => fake()->unique()->numerify('############')];
    }
}
