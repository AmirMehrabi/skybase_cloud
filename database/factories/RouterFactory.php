<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Router>
 */
class RouterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isOnline = fake()->boolean(80);

        return [
            'name' => fake()->randomElement(['Core-Router-1', 'Edge-Router-1', 'Tower-Router-3', 'HQ-Router', 'Branch-Router-'.fake()->numberBetween(1, 5)]),
            'model' => fake()->randomElement(['CCR1036-12G-4S', 'CCR1016-12G', 'RB4011iGS+', 'RB750Gr3', 'CCR2116-12G-4S+']),
            'vendor' => fake()->randomElement(['Mikrotik', 'Cisco', 'Juniper', 'Huawei']),
            'ip_address' => '192.168.'.fake()->numberBetween(1, 254).'.'.fake()->numberBetween(1, 254),
            'api_port' => 8728,
            'api_username' => 'admin',
            'api_password' => fake()->password(),
            'ssh_port' => 22,
            'location' => fake()->randomElement(['Data Center', 'Tower A', 'Tower B', 'HQ Building', 'Branch Office']),
            'site' => fake()->randomElement(['Main Site', 'North Tower', 'South Tower', 'East Wing', 'West Wing']),
            'status' => $isOnline ? 'online' : 'offline',
            'version' => fake()->randomElement(['v7.10', 'v7.11', 'v7.12', 'v7.13']),
            'uptime' => $isOnline ? fake()->randomElement(['1d 12h', '5d 8h', '12d 4h', '25d 18h', '45d 6h']) : null,
            'cpu_usage' => $isOnline ? fake()->numberBetween(5, 85) : 0,
            'memory_usage' => $isOnline ? fake()->numberBetween(20, 75) : 0,
            'active_sessions_count' => $isOnline ? fake()->numberBetween(10, 150) : 0,
            'total_customers' => fake()->numberBetween(20, 200),
            'enable_monitoring' => true,
            'enable_provisioning' => true,
            'timeout' => 30,
        ];
    }

    /**
     * Indicate that the router is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'uptime' => fake()->randomElement(['1d 12h', '5d 8h', '12d 4h', '25d 18h']),
            'cpu_usage' => fake()->numberBetween(5, 85),
            'memory_usage' => fake()->numberBetween(20, 75),
            'active_sessions_count' => fake()->numberBetween(10, 150),
        ]);
    }

    /**
     * Indicate that the router is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'uptime' => null,
            'cpu_usage' => 0,
            'memory_usage' => 0,
            'active_sessions_count' => 0,
        ]);
    }
}
