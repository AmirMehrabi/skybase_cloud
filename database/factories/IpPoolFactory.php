<?php

namespace Database\Factories;

use App\Models\IpPool;
use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IpPool>
 */
class IpPoolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = IpPool::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $networks = [
            ['address' => '10.10.0.0', 'cidr' => 24, 'name' => 'Main Office Network'],
            ['address' => '192.168.100.0', 'cidr' => 24, 'name' => 'Guest WiFi Network'],
            ['address' => '172.16.0.0', 'cidr' => 24, 'name' => 'Server Farm Network'],
            ['address' => '10.20.0.0', 'cidr' => 24, 'name' => 'IoT Devices Network'],
            ['address' => '10.30.0.0', 'cidr' => 24, 'name' => 'Corporate Network'],
        ];

        $network = fake()->randomElement($networks);
        $sites = ['Downtown Office', 'Main Street Location', 'Tech Park Building A', 'West Street', 'Industrial District Hub'];
        $types = ['dynamic', 'static', 'mixed'];
        $statuses = ['active', 'active', 'active', 'exhausted']; // Weighted towards active

        return [
            'name' => $network['name'].' '.fake()->randomNumber(2),
            'tenant_id' => Tenant::inRandomOrder()->first()?->id,
            'router_id' => Router::inRandomOrder()->first()?->id,
            'network_address' => $network['address'],
            'cidr' => $network['cidr'],
            'gateway' => preg_replace('/\.0$/', '.1', $network['address']),
            'dns_primary' => fake()->randomElement(['8.8.8.8', '1.1.1.1', '9.9.9.9']),
            'dns_secondary' => fake()->randomElement(['8.8.4.4', '1.0.0.1', '8.26.56.26']),
            'vlan_id' => fake()->numberBetween(100, 4094),
            'type' => fake()->randomElement($types),
            'status' => fake()->randomElement($statuses),
            'allow_static' => fake()->boolean(80),
            'auto_assign' => fake()->boolean(70),
            'block_reserved' => fake()->boolean(30),
            'site' => fake()->randomElement($sites),
            'total_ips' => 254,
            'used_ips' => fake()->numberBetween(0, 200),
            'reserved_ips' => fake()->numberBetween(0, 20),
            'available_ips' => 0, // Will be calculated
        ];
    }

    /**
     * Configure the pool with calculated statistics.
     */
    public function configure(): IpPoolFactory
    {
        return $this->afterMaking(function (IpPool $pool) {
            $pool->available_ips = $pool->total_ips - $pool->used_ips - $pool->reserved_ips;
            $pool->available_ips = max(0, $pool->available_ips);
        });
    }

    /**
     * Indicate that the pool is active.
     */
    public function active(): IpPoolFactory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the pool is exhausted.
     */
    public function exhausted(): IpPoolFactory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'exhausted',
            'used_ips' => 254,
            'available_ips' => 0,
        ]);
    }

    /**
     * Indicate that the pool is dynamic.
     */
    public function dynamic(): IpPoolFactory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'dynamic',
        ]);
    }

    /**
     * Indicate that the pool is static.
     */
    public function static(): IpPoolFactory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'static',
        ]);
    }

    /**
     * Indicate that the pool is mixed.
     */
    public function mixed(): IpPoolFactory
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'mixed',
        ]);
    }
}
