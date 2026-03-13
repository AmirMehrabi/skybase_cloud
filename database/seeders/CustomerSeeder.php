<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a realistic distribution of customers
        $activeCount = 35;
        $suspendedCount = 5;
        $pendingCount = 5;
        $overdueCount = 8;
        $individualCount = 40;
        $businessCount = 13;

        // Active customers (majority)
        Customer::factory()
            ->count($activeCount)
            ->active()
            ->individual()
            ->create();

        // Business customers
        Customer::factory()
            ->count(min($businessCount, 10))
            ->active()
            ->business()
            ->create();

        // Suspended customers
        Customer::factory()
            ->count($suspendedCount)
            ->suspended()
            ->create();

        // Pending customers
        Customer::factory()
            ->count($pendingCount)
            ->state(['status' => 'pending'])
            ->create();

        // Overdue customers (some active, some suspended)
        Customer::factory()
            ->count($overdueCount)
            ->overdue()
            ->create();
    }
}
