<?php

namespace Tests\Feature;

use App\Models\AccessPoint;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CoverageGeometry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function setupSite(): Site
    {
        $tenant = Tenant::query()->create(['id' => fake()->uuid(), 'name' => 'Network', 'slug' => fake()->unique()->slug(), 'company_name' => 'Network', 'email' => fake()->email(), 'timezone' => 'UTC', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);
        $this->actingAs($user);

        return Site::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function payload(): array
    {
        return ['version' => 0, 'name' => 'North coverage', 'technology' => 'wireless', 'evidence' => 'declared', 'availability' => 'survey_required', 'status' => 'draft', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[51, 35], [52, 35], [52, 36], [51, 36], [51, 35]]]]];
    }

    public function test_editor_renders_and_coverage_keeps_published_revision_when_draft_changes(): void
    {
        $site = $this->setupSite();
        $this->get(route('sites.coverage', $site))->assertOk()->assertSee('Draw polygon');
        $url = route('sites.coverage.save', $site);
        $first = $this->postJson($url, [...$this->payload(), 'status' => 'published'])->assertOk()->json('areas.0');
        $this->assertNotNull($first['published_revision_id']);
        $second = $this->postJson($url.'/'.$first['id'], [...$this->payload(), 'version' => 1, 'name' => 'Changed draft'])->assertOk()->json('areas.0');
        $this->assertEquals($first['published_revision_id'], $second['published_revision_id']);
        $this->assertCount(2, $second['revisions']);
        $this->postJson($url.'/'.$first['id'], [...$this->payload(), 'version' => 2, 'status' => 'archived'])->assertOk()->assertJsonPath('areas.0.published_revision_id', null);
    }

    public function test_overview_only_displays_published_geometry(): void
    {
        $site = $this->setupSite();
        $url = route('sites.coverage.save', $site);
        $area = $this->postJson($url, $this->payload())->assertOk()->json('id');
        $this->getJson(route('sites.map-data'))->assertJsonCount(0, 'coverage.features');
        $this->postJson($url.'/'.$area, [...$this->payload(), 'version' => 1, 'status' => 'published'])->assertOk();
        $this->getJson(route('sites.map-data'))->assertJsonCount(1, 'coverage.features')->assertJsonPath('coverage.features.0.properties.name', 'North coverage');
    }

    public function test_stale_save_is_rejected_without_creating_revision(): void
    {
        $site = $this->setupSite();
        $url = route('sites.coverage.save', $site);
        $id = $this->postJson($url, $this->payload())->assertOk()->json('id');
        $this->postJson($url.'/'.$id, $this->payload())->assertConflict();
        $this->assertDatabaseCount('coverage_revisions', 1);
    }

    public function test_other_tenant_cannot_read_or_modify_area(): void
    {
        $site = $this->setupSite();
        $url = route('sites.coverage.save', $site);
        $id = $this->postJson($url, $this->payload())->assertOk()->json('id');
        $other = $this->setupSite();
        $this->get(route('sites.coverage', $site))->assertNotFound();
        $this->postJson($url.'/'.$id, $this->payload())->assertNotFound();
        $this->postJson(route('sites.coverage.save', $other).'/'.$id, $this->payload())->assertNotFound();
    }

    public function test_invalid_geometry_and_unverified_claim_are_rejected(): void
    {
        $site = $this->setupSite();
        $url = route('sites.coverage.save', $site);
        $data = $this->payload();
        $data['geometry']['coordinates'][0] = [[51, 35], [52, 36], [51, 36], [52, 35], [51, 35]];
        $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('geometry');
        $this->postJson($url, [...$this->payload(), 'evidence' => 'verified'])->assertUnprocessable()->assertJsonValidationErrors('verified_on');
        $this->assertDatabaseCount('coverage_areas', 0);
    }

    public function test_holes_and_multipolygons_validate_and_malformed_coordinates_fail(): void
    {
        $geometry = $this->payload()['geometry'];
        $geometry['coordinates'][] = [[51.2, 35.2], [51.4, 35.2], [51.4, 35.4], [51.2, 35.4], [51.2, 35.2]];
        $validator = new CoverageGeometry;
        $this->assertTrue($validator->valid($geometry));
        $this->assertTrue($validator->valid(['type' => 'MultiPolygon', 'coordinates' => [$geometry['coordinates']]]));
        $geometry['coordinates'][1][0] = [200, 95];
        $this->assertFalse($validator->valid($geometry));
        $this->assertFalse($validator->valid(['type' => 'Polygon', 'coordinates' => ['invalid']]));
    }

    public function test_area_cannot_use_access_point_from_another_site(): void
    {
        $site = $this->setupSite();
        $other = Site::factory()->create(['tenant_id' => $site->tenant_id]);
        $ap = AccessPoint::factory()->create(['tenant_id' => $site->tenant_id, 'site_id' => $other->id]);
        $this->postJson(route('sites.coverage.save', $site), [...$this->payload(), 'access_point_id' => $ap->id])->assertUnprocessable()->assertJsonValidationErrors('access_point_id');
    }
}
