<?php

namespace Database\Factories;

use App\Models\CommunicationAudience;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunicationAudience> */
class CommunicationAudienceFactory extends Factory
{
    public function definition(): array
    {
        return ['tenant_id' => tenant_id(), 'name' => fake()->sentence(2), 'filters' => ['status' => 'active']];
    }
}
