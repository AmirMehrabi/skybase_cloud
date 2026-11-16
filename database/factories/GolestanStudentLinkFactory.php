<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\GolestanStudentLink;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GolestanStudentLink> */
class GolestanStudentLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => tenant_id() ?? Tenant::factory(),
            'student_number' => fake()->unique()->numerify('########'),
            'customer_id' => fn (array $attributes) => Customer::factory()->create(['tenant_id' => $attributes['tenant_id']])->id,
        ];
    }
}
