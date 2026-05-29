<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TicketTeam;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketTeam>
 */
class TicketTeamFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['General Support', 'Billing', 'Network Operations', 'Provisioning', 'Field Service']).' '.fake()->unique()->randomNumber(3);

        return [
            'tenant_id' => Tenant::query()->value('id') ?? (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'status' => 'active',
            'assignment_strategy' => TicketTeam::STRATEGY_QUEUE,
            'default_user_id' => null,
            'first_response_minutes' => 240,
            'resolution_minutes' => 2880,
            'sort_order' => 0,
        ];
    }

    public function randomAssignment(): static
    {
        return $this->state(fn (): array => [
            'assignment_strategy' => TicketTeam::STRATEGY_RANDOM,
        ]);
    }
}
