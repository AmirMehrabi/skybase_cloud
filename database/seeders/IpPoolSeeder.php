<?php

namespace Database\Seeders;

use App\Models\IpPool;
use App\Models\Router;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class IpPoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first tenant (you might want to make this more specific)
        $tenant = Tenant::first();

        if (! $tenant) {
            $this->command->warn('No tenants found. Skipping IP pool seeding.');

            return;
        }

        // Get or create a router for this tenant
        $router = Router::where('tenant_id', $tenant->id)->first();

        if (! $router) {
            $this->command->warn('No routers found for tenant. Skipping IP pool seeding.');

            return;
        }

        // Create IP pools
        $pools = [
            [
                'name' => 'Main Office Network',
                'network_address' => '10.10.0.0',
                'cidr' => 24,
                'gateway' => '10.10.0.1',
                'dns_primary' => '8.8.8.8',
                'dns_secondary' => '8.8.4.4',
                'vlan_id' => 100,
                'type' => 'mixed',
                'site' => 'Downtown Office',
            ],
            [
                'name' => 'Branch Office Network',
                'network_address' => '10.20.0.0',
                'cidr' => 24,
                'gateway' => '10.20.0.1',
                'dns_primary' => '8.8.8.8',
                'dns_secondary' => '8.8.4.4',
                'vlan_id' => 200,
                'type' => 'dynamic',
                'site' => 'Branch Office',
            ],
            [
                'name' => 'Guest Network',
                'network_address' => '192.168.100.0',
                'cidr' => 24,
                'gateway' => '192.168.100.1',
                'dns_primary' => '1.1.1.1',
                'dns_secondary' => '1.0.0.1',
                'vlan_id' => 300,
                'type' => 'dynamic',
                'site' => 'Main Lobby',
            ],
        ];

        foreach ($pools as $poolData) {
            $pool = IpPool::create([
                'tenant_id' => $tenant->id,
                'router_id' => $router->id,
                'total_ips' => 254,
                'available_ips' => 250,
                'used_ips' => 0,
                'reserved_ips' => 4,
                ...$poolData,
            ]);

            $this->command->info("Created IP pool: {$pool->name}");
        }
    }
}
