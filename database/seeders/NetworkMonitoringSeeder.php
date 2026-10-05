<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\NetworkAlert;
use App\Models\NetworkUsageRecord;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NetworkMonitoringSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            $routers = Router::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();

            if ($routers->isEmpty()) {
                return;
            }

            $customers = Customer::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->limit(25)->get();
            $plans = Plan::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->active()->get();

            $customers->each(function (Customer $customer, int $index) use ($tenant, $routers, $plans): void {
                $router = $routers->values()[$index % $routers->count()];
                $plan = $plans->isNotEmpty() ? $plans->values()[$index % $plans->count()] : null;

                $subscription = Subscription::query()->withoutGlobalScopes()->firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'customer_id' => $customer->id,
                    ],
                    [
                        'plan_id' => $plan?->id,
                        'router_id' => $router->id,
                        'site' => $router->site,
                        'connection_type' => 'pppoe',
                        'ip_address' => '10.'.($index + 10).'.'.($index % 250).'.'.(($index % 200) + 10),
                        'ip_management' => 'router',
                        'pppoe_username' => 'user'.$customer->id,
                        'pppoe_password' => Str::random(20),
                        'base_price' => $plan?->price ?? 0,
                        'total_price' => $plan?->price ?? 0,
                        'billing_cycle' => $plan?->billing_cycle ?? 'monthly',
                        'status' => $customer->status === 'active' ? 'active' : 'pending',
                        'start_date' => now()->subMonths(rand(1, 18)),
                        'activation_date' => now()->subMonths(rand(1, 18)),
                    ],
                );

                NetworkUsageRecord::query()->withoutGlobalScopes()->firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'subscription_id' => $subscription->id,
                    'router_id' => $router->id,
                ], [
                    'ip_address' => $subscription->ip_address,
                    'download_bytes' => random_int(1073741824, 536870912000),
                    'upload_bytes' => random_int(104857600, 107374182400),
                    'session_seconds' => random_int(1800, 2592000),
                    'started_at' => now()->subDays(random_int(1, 30)),
                    'ended_at' => null,
                    'last_activity_at' => now(),
                ]);
            });

            $routers->each(function (Router $router): void {
                if ($router->status === 'offline') {
                    NetworkAlert::query()->withoutGlobalScopes()->firstOrCreate([
                        'tenant_id' => $router->tenant_id,
                        'router_id' => $router->id,
                        'severity' => 'critical',
                        'category' => 'connectivity',
                        'message' => 'Router offline - No response to ping',
                        'status' => 'active',
                    ], [
                        'occurred_at' => now()->subMinutes(rand(10, 120)),
                    ]);
                }

                if ($router->cpu_usage > 70 || $router->memory_usage > 70) {
                    NetworkAlert::query()->withoutGlobalScopes()->firstOrCreate([
                        'tenant_id' => $router->tenant_id,
                        'router_id' => $router->id,
                        'severity' => 'warning',
                        'category' => 'performance',
                        'message' => 'High resource usage detected',
                        'status' => 'active',
                    ], [
                        'occurred_at' => now()->subMinutes(rand(5, 90)),
                    ]);
                }
            });
        });
    }
}
