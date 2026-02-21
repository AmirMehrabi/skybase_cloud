<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for small ISPs getting started',
                'price' => 49,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 100 customers',
                    'Up to 5 routers',
                    'Up to 10 users',
                    'Basic billing & invoicing',
                    'Router management',
                    'Email support',
                ],
                'max_customers' => 100,
                'max_routers' => 5,
                'max_users' => 10,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For growing ISPs with more demands',
                'price' => 149,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 1,000 customers',
                    'Up to 25 routers',
                    'Up to 25 users',
                    'Advanced billing & invoicing',
                    'Router management',
                    'Network monitoring',
                    'Reports & analytics',
                    'Priority support',
                ],
                'max_customers' => 1000,
                'max_routers' => 25,
                'max_users' => 25,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large-scale ISP operations',
                'price' => 399,
                'billing_cycle' => 'monthly',
                'features' => [
                    'Unlimited customers',
                    'Unlimited routers',
                    'Unlimited users',
                    'Advanced billing & invoicing',
                    'Router management',
                    'Network monitoring',
                    'Advanced reports & analytics',
                    'API access',
                    'White-label options',
                    'Dedicated support',
                    'Custom integrations',
                ],
                'max_customers' => 999999,
                'max_routers' => 999999,
                'max_users' => 999999,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
