<?php

namespace Database\Seeders;

use App\Models\CoverageArea;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CoverageSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            Site::query()->where('tenant_id', $tenant->id)->each(function (Site $site): void {
                $area = CoverageArea::query()->firstOrCreate(['tenant_id' => $site->tenant_id, 'site_id' => $site->id], ['version' => 1]);
                if ($area->revisions()->exists()) {
                    return;
                }
                $x = (float) $site->longitude;
                $y = (float) $site->latitude;
                $area->revisions()->create(['tenant_id' => $site->tenant_id, 'snapshot' => ['name' => $site->name.' example', 'status' => 'draft', 'technology' => 'wireless', 'evidence' => 'declared', 'availability' => 'survey_required', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[$x, $y], [$x + .01, $y], [$x + .01, $y + .01], [$x, $y]]]]]]);
            });
        });
    }
}
