<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Main POP', 'North Tower', 'South Tower', 'Central Office', 'East Distribution Hub', 'West Relay']);

        return [
            'tenant_id' => Tenant::query()->inRandomOrder()->value('id') ?? 'test-tenant',
            'code' => strtoupper(fake()->unique()->bothify('SITE-###')),
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(25, 39),
            'longitude' => fake()->longitude(44, 63),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }
}
