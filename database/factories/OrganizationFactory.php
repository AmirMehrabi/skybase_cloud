<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $billingEnabled = fake()->boolean(35);
        $discountType = fake()->randomElement(['none', 'fixed', 'percentage']);

        return [
            'tenant_id' => Tenant::query()->inRandomOrder()->value('id') ?? DB::table('tenants')->inRandomOrder()->value('id') ?? 1,
            'code' => Organization::generateCode(),
            'name' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'billing_enabled' => $billingEnabled,
            'billing_disabled_at' => $billingEnabled ? null : now(),
            'default_plan_id' => $billingEnabled ? Plan::query()->active()->inRandomOrder()->value('id') : null,
            'default_billing_cycle' => $billingEnabled ? fake()->randomElement(['monthly', 'quarterly', 'yearly']) : null,
            'default_grace_period_days' => $billingEnabled ? fake()->numberBetween(0, 45) : null,
            'default_discount_type' => $discountType,
            'default_discount_amount' => $discountType === 'none' ? 0 : fake()->randomFloat(2, 1, 20),
            'default_tax_percentage' => fake()->randomFloat(2, 0, 15),
        ];
    }

    public function billingEnabled(?Plan $plan = null): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_enabled' => true,
            'billing_disabled_at' => null,
            'default_plan_id' => $plan?->id ?? Plan::factory()->create(['status' => 'active'])->id,
            'default_billing_cycle' => 'monthly',
            'default_grace_period_days' => 7,
            'default_discount_type' => 'none',
            'default_discount_amount' => 0,
            'default_tax_percentage' => 0,
        ]);
    }
}
