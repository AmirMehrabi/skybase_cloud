<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TicketTeam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketTeamSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'General Support', 'description' => 'Customer support and general service requests.', 'sort_order' => 10],
            ['name' => 'Billing', 'description' => 'Invoices, payments, credits, and account billing.', 'sort_order' => 20],
            ['name' => 'Network Operations', 'description' => 'Connectivity, router, VPN, and network incidents.', 'sort_order' => 30],
        ];

        Tenant::query()->each(function (Tenant $tenant) use ($defaults): void {
            foreach ($defaults as $team) {
                TicketTeam::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'slug' => Str::slug($team['name']),
                    ],
                    [
                        'name' => $team['name'],
                        'description' => $team['description'],
                        'status' => 'active',
                        'assignment_strategy' => TicketTeam::STRATEGY_QUEUE,
                        'first_response_minutes' => 240,
                        'resolution_minutes' => 2880,
                        'sort_order' => $team['sort_order'],
                    ]
                );
            }
        });
    }
}
