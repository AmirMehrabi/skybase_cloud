<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\NetworkUsageRecord;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkUsageRecord>
 */
class NetworkUsageRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscription = Subscription::query()->with(['customer', 'router'])->inRandomOrder()->first();
        $customer = $subscription?->customer ?? Customer::query()->inRandomOrder()->first();
        $router = $subscription?->router ?? Router::query()->inRandomOrder()->first();

        return [
            'tenant_id' => $subscription?->tenant_id ?? $customer?->tenant_id ?? $router?->tenant_id ?? Tenant::query()->inRandomOrder()->value('id') ?? 'test-tenant',
            'customer_id' => $customer?->id,
            'subscription_id' => $subscription?->id,
            'router_id' => $router?->id,
            'ip_address' => $subscription?->ip_address ?? fake()->ipv4(),
            'download_bytes' => fake()->numberBetween(1073741824, 536870912000),
            'upload_bytes' => fake()->numberBetween(104857600, 107374182400),
            'session_seconds' => fake()->numberBetween(1800, 2592000),
            'started_at' => fake()->dateTimeBetween('-30 days', '-1 hour'),
            'ended_at' => null,
            'last_activity_at' => fake()->dateTimeBetween('-24 hours', 'now'),
        ];
    }
}
