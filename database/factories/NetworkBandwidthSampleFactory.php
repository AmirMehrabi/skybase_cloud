<?php

namespace Database\Factories;

use App\Models\NetworkBandwidthSample;
use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkBandwidthSample>
 */
class NetworkBandwidthSampleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $router = Router::query()->inRandomOrder()->first();
        $capacity = fake()->randomElement([1000000000, 2500000000, 10000000000]);
        $download = fake()->numberBetween((int) ($capacity * 0.05), (int) ($capacity * 0.65));
        $upload = fake()->numberBetween((int) ($capacity * 0.02), (int) ($capacity * 0.25));

        return [
            'tenant_id' => $router?->tenant_id ?? Tenant::query()->inRandomOrder()->value('id') ?? 'test-tenant',
            'router_id' => $router?->id,
            'interface_name' => fake()->randomElement(['ether1-gateway', 'ether2-lan', 'ether3-wan', 'sfp1-uplink']),
            'download_bps' => $download,
            'upload_bps' => $upload,
            'capacity_bps' => $capacity,
            'sampled_at' => fake()->dateTimeBetween('-24 hours', 'now'),
        ];
    }
}
