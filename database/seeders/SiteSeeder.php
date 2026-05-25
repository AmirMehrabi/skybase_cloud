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
                $sites = Site::factory()
                    ->count(3)
                    ->create(['tenant_id' => $tenant->id]);
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
