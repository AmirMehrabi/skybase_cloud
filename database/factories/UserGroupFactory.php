<?php

namespace Database\Factories;

use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<UserGroup>
 */
class UserGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => DB::table('tenants')->inRandomOrder()->value('id'),
            'name' => fake()->unique()->company().' Group',
            'description' => fake()->optional()->sentence(),
        ];
    }
}
