<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\IpAddress;
use App\Models\IpPool;
use App\Models\Subscription;
use App\Models\SubscriptionIpRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionIpRoute>
 */
class SubscriptionIpRouteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscription = Subscription::query()->inRandomOrder()->first();
        $tenantId = $subscription?->tenant_id;
        $ipPool = IpPool::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->inRandomOrder()
            ->first() ?? IpPool::factory()->create(['tenant_id' => $tenantId]);
        $subscription ??= Subscription::query()->create([
            'tenant_id' => $ipPool->tenant_id,
            'customer_id' => Customer::factory()->create(['tenant_id' => $ipPool->tenant_id])->id,
            'subscription_code' => 'SUB-'.fake()->unique()->numerify('######'),
            'connection_type' => 'pppoe',
            'ip_management' => 'system',
            'ip_pool_id' => $ipPool->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);
        $ipAddress = IpAddress::query()
            ->where('ip_pool_id', $ipPool->id)
            ->where('status', 'available')
            ->inRandomOrder()
            ->first();

        return [
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'ip_pool_id' => $ipPool->id,
            'ip_address_id' => $ipAddress?->id,
            'ip_address' => $ipAddress?->ip_address ?? fake()->ipv4(),
            'cidr' => 32,
            'routeros_sync_status' => 'pending',
        ];
    }
}
