<?php

namespace Database\Factories;

use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactInquiry>
 */
class ContactInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'company_name' => fake()->optional()->company(),
            'subject' => fake()->randomElement(['Sales question', 'Partnership', 'Migration help', 'General inquiry']),
            'message' => fake()->paragraph(),
            'source_page' => 'contact',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
