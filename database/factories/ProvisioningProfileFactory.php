<?php

namespace Database\Factories;

use App\Models\ProvisioningProfile;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProvisioningProfile> */
class ProvisioningProfileFactory extends Factory
{
    protected $model = ProvisioningProfile::class;

    public function definition(): array
    {
        return ['tenant_id' => tenant_id() ?? Tenant::factory(), 'name' => 'hAP lite Wi-Fi', 'manufacturer' => 'MikroTik', 'model' => 'hAP lite', 'parameters' => [['path' => 'Device.WiFi.SSID.1.SSID', 'value' => 'SkyBase']]];
    }
}
