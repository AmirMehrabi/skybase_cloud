<?php

namespace Database\Factories;

use App\Models\CoverageArea;
use App\Models\CoverageRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoverageRevision>
 */
class CoverageRevisionFactory extends Factory
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
            'coverage_area_id' => fn (array $attributes) => CoverageArea::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
            'snapshot' => ['name' => 'Example coverage', 'status' => 'draft', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[51, 35], [52, 35], [52, 36], [51, 35]]]]],
        ];
    }
}
