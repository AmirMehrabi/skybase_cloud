<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantNoteFactory extends Factory
{
    protected $model = TenantNote::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'body' => fake()->sentence()];
    }
}
