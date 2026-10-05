<?php

namespace Database\Seeders;

use App\Models\Router;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            $sites = Site::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->get();

            if ($sites->isEmpty()) {
                $sites = collect([
                    ['code' => 'SITE-001', 'name' => 'Main POP', 'latitude' => 35.6892, 'longitude' => 51.3890],
                    ['code' => 'SITE-002', 'name' => 'North Tower', 'latitude' => 35.7500, 'longitude' => 51.4000],
                    ['code' => 'SITE-003', 'name' => 'South Tower', 'latitude' => 35.6000, 'longitude' => 51.4000],
                ])->map(fn (array $site): Site => Site::query()->create([
                    'tenant_id' => $tenant->id,
                    'status' => 'active',
                    ...$site,
                ]));
            }

            Router::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNull('site_id')
                ->get()
                ->each(function (Router $router, int $index) use ($sites): void {
                    $router->forceFill([
                        'site_id' => $sites->values()[$index % $sites->count()]->id,
                    ])->save();
                });
        });
    }
}
