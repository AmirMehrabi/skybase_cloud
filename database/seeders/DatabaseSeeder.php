<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'test-tenant'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Tenant',
                'company_name' => 'Test Tenant',
                'email' => 'test@example.com',
                'timezone' => 'UTC',
                'status' => 'active',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Test User',
                'password' => 'password1@1@',
                'role' => 'admin',
                'status' => 'active',
            ],
        );

        $this->call([
            SiteSeeder::class,
            IpPoolSeeder::class,
            NetworkMonitoringSeeder::class,
            TicketTeamSeeder::class,
        ]);
    }
}
