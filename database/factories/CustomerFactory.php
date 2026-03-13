<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customerType = fake()->randomElement(['individual', 'business']);
        $plans = ['Fiber 50 Mbps', 'Fiber 100 Mbps', 'Fiber 200 Mbps', 'Fiber 500 Mbps', 'Fiber 1 Gbps'];
        $sites = ['Downtown Hub', 'Business Park', 'North Tower', 'South Station', 'West Station', 'East Center'];
        $routers = ['Mikrotik-01', 'Mikrotik-02', 'Mikrotik-03', 'Cisco-01', 'Cisco-02', 'Cisco-03'];
        $billingCycles = ['monthly', 'quarterly', 'yearly'];
        $statuses = ['pending', 'active', 'active', 'active', 'suspended', 'inactive'];

        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        $data = [
            'tenant_id' => DB::table('tenants')->inRandomOrder()->value('id') ?? 1,
            'customer_code' => Customer::generateCustomerCode(),
            'customer_type' => $customerType,
            'first_name' => $customerType === 'individual' ? $firstName : null,
            'last_name' => $customerType === 'individual' ? $lastName : null,
            'company_name' => $customerType === 'business' ? fake()->company() : null,
            'name' => $customerType === 'individual' ? "{$firstName} {$lastName}" : fake()->company(),
            'national_id' => fake()->optional(0.7)->ssn(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'whatsapp' => fake()->optional(0.6)->phoneNumber(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional(0.3)->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->randomElement(['United States', 'Canada', 'United Kingdom', 'Germany', 'France', 'Australia']),
            'plan' => fake()->randomElement($plans),
            'site' => fake()->randomElement($sites),
            'router' => fake()->randomElement($routers),
            'ip_address' => fake()->optional(0.8)->ipv4(),
            'pppoe_username' => fake()->optional(0.8)->userName(),
            'pppoe_password' => fake()->optional(0.8)->password(8, 16),
            'billing_type' => fake()->randomElement(['prepaid', 'postpaid']),
            'billing_cycle' => fake()->randomElement($billingCycles),
            'balance' => fake()->randomFloat(2, -500, 500),
            'credit_limit' => fake()->randomFloat(2, 0, 1000),
            'tax_exempt' => fake()->boolean(10),
            'status' => fake()->randomElement($statuses),
        ];

        // Add activation date for active customers
        if ($data['status'] === 'active' && fake()->boolean(80)) {
            $data['activation_date'] = fake()->dateTimeBetween('-2 years', '-1 day');
        }

        return $data;
    }

    /**
     * Indicate that the customer is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'activation_date' => fake()->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }

    /**
     * Indicate that the customer is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the customer has an overdue balance.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => fake()->randomFloat(2, 100, 2000),
            'status' => fake()->randomElement(['active', 'suspended']),
        ]);
    }

    /**
     * Indicate that the customer is an individual.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'individual',
        ]);
    }

    /**
     * Indicate that the customer is a business.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
        ]);
    }
}
