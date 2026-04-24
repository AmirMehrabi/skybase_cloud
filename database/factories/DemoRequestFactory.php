<?php

namespace Database\Factories;

use App\Models\DemoRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemoRequest>
 */
class DemoRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requested_plan' => fake()->randomElement(['Basic', 'Standard', 'Advanced', 'Professional', 'Premium', 'Enterprise']),
            'business_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'country' => fake()->country(),
            'company_website' => fake()->optional()->url(),
            'customer_count' => fake()->numberBetween(10, 10000),
            'current_system' => fake()->optional()->randomElement(['Spreadsheets', 'Legacy billing tool', 'Custom ERP', 'MikroTik only']),
            'deployment_timeline' => fake()->optional()->randomElement(['Immediately', 'This month', 'This quarter', 'Just exploring']),
            'message' => fake()->optional()->paragraph(),
            'source_page' => 'pricing',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
