<?php

namespace Database\Factories;

use App\Models\NetflowFlow;
use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NetflowFlow>
 */
class NetflowFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'company_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $router = Router::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'tenant_id' => $router->tenant_id,
            'router_id' => $router->id,
            'exporter_ip' => $router->ip_address,
            'source_ip' => fake()->ipv4(),
            'destination_ip' => fake()->ipv4(),
            'source_port' => fake()->numberBetween(1024, 65535),
            'destination_port' => fake()->randomElement([53, 80, 443, 8291]),
            'protocol' => fake()->randomElement([1, 6, 17]),
            'bytes' => fake()->numberBetween(1000, 10000000),
            'packets' => fake()->numberBetween(10, 10000),
            'flow_started_at' => now()->subMinutes(fake()->numberBetween(1, 30)),
            'flow_ended_at' => now()->subMinutes(fake()->numberBetween(0, 10)),
            'received_at' => now(),
        ];
    }
}
