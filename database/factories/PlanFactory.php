<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ucwords($name),
            'internal_name' => strtolower(str_replace(' ', '_', $name)).'_'.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive', 'archived']),
            'visibility' => fake()->randomElement(['public', 'private', 'hidden']),
            'type' => fake()->randomElement(['pppoe', 'hotspot', 'static', 'dhcp', 'fiber', 'wireless']),
            'category' => fake()->randomElement(['Residential', 'Business', 'Enterprise']),
            'download_speed' => fake()->numberBetween(5, 1000),
            'upload_speed' => fake()->numberBetween(1, 500),
            'burst_download' => fake()->numberBetween(0, 1000),
            'burst_upload' => fake()->numberBetween(0, 500),
            'bandwidth_unit' => fake()->randomElement(['Kbps', 'Mbps', 'Gbps']),
            'data_limit' => fake()->boolean() ? fake()->numberBetween(10, 5000) : null,
            'data_unit' => fake()->randomElement(['MB', 'GB', 'TB']),
            'unlimited' => fake()->boolean(),
            'price' => fake()->randomFloat(2, 9.99, 499.99),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'billing_cycle' => fake()->randomElement(['daily', 'weekly', 'monthly', 'quarterly', 'yearly']),
            'setup_fee' => fake()->randomFloat(2, 0, 100),
            'tax_profile' => fake()->optional()->word(),
            'router_profile' => fake()->optional()->word(),
            'ip_pool' => fake()->optional()->word(),
            'priority' => fake()->numberBetween(1, 10),
            'contract_required' => fake()->boolean(),
            'contract_duration' => fake()->boolean() ? fake()->numberBetween(1, 36) : null,
            'available_from' => fake()->optional()->date(),
            'available_to' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
