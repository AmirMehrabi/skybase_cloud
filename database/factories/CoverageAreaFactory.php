<?php

namespace Database\Factories;

use App\Models\CoverageArea;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoverageArea>
 */
class CoverageAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => tenant_id() ?? 'test-tenant',
            'site_id' => fn (array $attributes) => Site::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
            'version' => 0,
        ];
    }
}
