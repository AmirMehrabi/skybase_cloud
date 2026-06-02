<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerNote>
 */
class CustomerNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customer = Customer::factory()->create();

        return [
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'user_id' => User::factory()->create(['tenant_id' => $customer->tenant_id])->id,
            'body' => fake()->paragraph(),
        ];
    }
}
