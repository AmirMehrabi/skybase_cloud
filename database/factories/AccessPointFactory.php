<?php

namespace Database\Factories;

use App\Models\AccessPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessPoint>
 */
class AccessPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isOnline = fake()->boolean(75);

        return [
            'name' => fake()->randomElement(['Tower-A-AP1', 'Tower-B-AP2', 'HQ-AP-5G', 'Branch-AP1', 'Site-AP-'.fake()->numberBetween(1, 20)]),
            'model' => fake()->randomElement(['LiteBeam 5AC', 'NanoStation 5AC', 'Rocket 5AC', 'hAP ac²', 'cAP ac', 'OmniTik 5HacD']),
            'vendor' => fake()->randomElement(['Ubiquiti', 'TP-Link', 'MikroTik', 'Cambium', 'Ruckus']),
            'mac_address' => strtoupper(fake()->regexify('[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}:[0-9A-F]{2}')),
            'ip_address' => '192.168.'.fake()->numberBetween(10, 50).'.'.fake()->numberBetween(10, 254),
            'serial_number' => strtoupper(fake()->bothify('####-####-####')),
            'firmware_version' => fake()->randomElement(['v8.7.0', 'v8.6.0', 'v7.12.0', 'v6.48.3']),
            'frequency_band' => fake()->randomElement(['2.4GHz', '5GHz', '6GHz', 'dual-band']),
            'channel' => fake()->randomElement(['36', '44', '149', 'Auto', '1', '6', '11']),
            'ssid' => fake()->randomElement(['SkyBase-5G', 'SkyBase-2.4G', 'Tower-AP', 'Customer-WiFi']),
            'tx_power' => fake()->numberBetween(10, 30),
            'antenna_type' => fake()->randomElement(['Omni', 'Directional', 'Sector']),
            'antenna_gain' => fake()->numberBetween(5, 20),
            'height_meters' => fake()->randomFloat(2, 5, 30),
            'azimuth' => fake()->numberBetween(0, 360),
            'coverage_angle' => fake()->randomElement([60, 90, 120, 180, 360]),
            'max_clients' => fake()->randomElement([50, 100, 150, 200, 300]),
            'connected_clients' => $isOnline ? fake()->numberBetween(5, 100) : 0,
            'status' => $isOnline ? 'online' : fake()->randomElement(['offline', 'maintenance']),
            'last_status_checked_at' => $isOnline ? fake()->dateTimeBetween('-1 hour', 'now') : null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the access point is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'connected_clients' => fake()->numberBetween(5, 100),
            'last_status_checked_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the access point is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'connected_clients' => 0,
        ]);
    }
}
