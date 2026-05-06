<?php

namespace Database\Factories;

use App\Models\NetworkAlert;
use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkAlert>
 */
class NetworkAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $router = Router::query()->inRandomOrder()->first();
        $severity = fake()->randomElement(['info', 'warning', 'critical']);

        return [
            'tenant_id' => $router?->tenant_id ?? Tenant::query()->inRandomOrder()->value('id') ?? 'test-tenant',
            'router_id' => $router?->id,
            'severity' => $severity,
            'category' => fake()->randomElement(['connectivity', 'performance', 'system']),
            'message' => match ($severity) {
                'critical' => 'Router offline - Connection timeout',
                'warning' => fake()->randomElement(['High CPU usage detected', 'Memory usage above threshold', 'Interface utilization above 80%']),
                default => fake()->randomElement(['Scheduled maintenance completed', 'Configuration backup completed']),
            },
            'status' => fake()->randomElement(['active', 'active', 'resolved']),
            'occurred_at' => fake()->dateTimeBetween('-12 hours', 'now'),
            'resolved_at' => null,
        ];
    }
}
