<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\IpAddress;
use App\Models\IpPool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IpAddress>
 */
class IpAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = IpAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['available', 'available', 'available', 'assigned', 'assigned', 'reserved', 'blocked'];
        $status = fake()->randomElement($statuses);

        $ipPool = IpPool::inRandomOrder()->first();
        if (! $ipPool) {
            // Fallback if no pools exist
            $ipPool = IpPool::factory()->create();
        }

        // Generate IP within the pool
        $networkAddress = $ipPool->network_address;
        $ipSuffix = fake()->numberBetween(1, 254);
        $ipAddress = preg_replace('/\.0$/', '.'.$ipSuffix, $networkAddress);

        return [
            'tenant_id' => $ipPool->tenant_id,
            'ip_pool_id' => $ipPool->id,
            'ip_address' => $ipAddress,
            'status' => $status,
            'customer_id' => $status === 'assigned' ? Customer::inRandomOrder()->first()?->id : null,
            'mac_address' => $status === 'assigned' ? fake()->macAddress() : null,
            'subscription_code' => $status === 'assigned' ? 'SUB-'.date('Y').'-'.str_pad(fake()->randomNumber(3), 3, '0', STR_PAD_LEFT) : null,
            'assigned_at' => $status === 'assigned' ? fake()->dateTimeThisYear() : null,
            'released_at' => null,
            'notes' => $status === 'blocked' ? 'Blocked - Abuse' : ($status === 'reserved' ? 'Reserved for VIP' : null),
            'metadata' => $status === 'assigned' ? [
                'device_type' => fake()->randomElement(['router', 'computer', 'phone', 'tablet', 'iot']),
                'last_seen' => fake()->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }

    /**
     * Indicate that the IP is available.
     */
    public function available(): IpAddressFactory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'customer_id' => null,
            'mac_address' => null,
            'subscription_code' => null,
            'assigned_at' => null,
        ]);
    }

    /**
     * Indicate that the IP is assigned.
     */
    public function assigned(): IpAddressFactory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
            'customer_id' => Customer::inRandomOrder()->first()?->id,
            'mac_address' => fake()->macAddress(),
            'subscription_code' => 'SUB-'.date('Y').'-'.str_pad(fake()->randomNumber(3), 3, '0', STR_PAD_LEFT),
            'assigned_at' => fake()->dateTimeThisYear(),
        ]);
    }

    /**
     * Indicate that the IP is reserved.
     */
    public function reserved(): IpAddressFactory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reserved',
            'customer_id' => null,
            'notes' => 'Reserved for VIP',
        ]);
    }

    /**
     * Indicate that the IP is blocked.
     */
    public function blocked(): IpAddressFactory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
            'customer_id' => null,
            'notes' => 'Blocked - Abuse',
        ]);
    }
}
