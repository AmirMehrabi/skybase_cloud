<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return ['id' => (string) Str::uuid(), 'name' => fake()->unique()->company(), 'slug' => (string) Str::uuid(), 'company_name' => fake()->company(), 'email' => fake()->unique()->safeEmail(), 'timezone' => 'UTC', 'status' => 'active'];
    }
}
