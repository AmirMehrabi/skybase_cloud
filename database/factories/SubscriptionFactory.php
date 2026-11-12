<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return ['tenant_id' => tenant_id() ?? Tenant::factory(), 'customer_id' => Customer::factory(), 'plan_id' => Plan::factory(), 'subscription_code' => 'SUB-'.fake()->unique()->numerify('########'), 'connection_type' => 'pppoe', 'pppoe_username' => 'cpe'.fake()->numerify('########'), 'pppoe_password' => 'test-password', 'base_price' => 50, 'total_price' => 50, 'billing_cycle' => 'monthly', 'billing_enabled' => false, 'status' => 'active'];
    }
}
