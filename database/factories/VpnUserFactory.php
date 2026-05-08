<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\VpnUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<VpnUser>
 */
class VpnUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::query()->inRandomOrder()->value('id') ?? 'test-tenant',
            'username' => fake()->unique()->userName(),
            'password_hash' => Hash::make('password'),
            'active' => fake()->boolean(85),
            'online' => false,
            'connected_at' => null,
            'disconnected_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'vpn_ip' => fake()->optional()->ipv4(),
            'real_ip' => fake()->optional()->ipv4(),
            'bytes_received' => fake()->numberBetween(0, 10737418240),
            'bytes_sent' => fake()->numberBetween(0, 10737418240),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes): array => [
            'online' => true,
            'connected_at' => now()->subMinutes(fake()->numberBetween(1, 180)),
            'disconnected_at' => null,
        ]);
    }
}
