<?php

namespace Tests\Feature;

use App\Models\CoverageArea;
use App\Models\Setting;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function isp(string $slug): Tenant
    {
        return Tenant::query()->create(['id' => fake()->uuid(), 'slug' => $slug, 'name' => $slug, 'company_name' => $slug, 'email' => fake()->email(), 'timezone' => 'UTC', 'status' => 'active']);
    }

    private function publish(Tenant $tenant, string $name): void
    {
        $site = Site::factory()->create(['tenant_id' => $tenant->id]);
        $area = CoverageArea::factory()->create(['tenant_id' => $tenant->id, 'site_id' => $site->id]);
        $snapshot = ['name' => $name, 'notes' => 'Secret technician note', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]]]];
        $published = $area->revisions()->create(['tenant_id' => $tenant->id, 'snapshot' => $snapshot]);
        $area->update(['published_revision_id' => $published->id]);
        $area->revisions()->create(['tenant_id' => $tenant->id, 'snapshot' => [...$snapshot, 'name' => 'Unreleased boundary']]);
    }

    public function test_feed_is_opt_in_scoped_and_only_exposes_public_revision_fields(): void
    {
        $isp = $this->isp('north');
        $this->publish($isp, 'North coverage');
        $this->publish($this->isp('south'), 'South coverage');
        $url = route('coverage.public.feed', 'north');
        $this->getJson($url)->assertNotFound();
        Setting::set('coverage.public', true, 'boolean', $isp->id);
        $this->getJson($url)->assertOk()->assertHeader('Access-Control-Allow-Origin', '*')->assertJsonCount(1, 'features')->assertJsonPath('features.0.properties.name', 'North coverage')->assertJsonMissingPath('features.0.properties.notes');
        $this->get(route('coverage.public.map', 'north'))->assertOk()->assertSee('Loading coverage');
        $owner = User::factory()->create(['tenant_id' => $isp->id, 'role' => 'owner', 'status' => 'active']);
        $this->actingAs($owner)->delete(route('sites.sharing'))->assertRedirect(route('sites.index'));
        $this->getJson($url)->assertNotFound();
        $this->get(route('coverage.public.map', 'north'))->assertNotFound();
    }

    public function test_only_owner_can_enable_public_sharing(): void
    {
        $isp = $this->isp('network');
        $staff = User::factory()->create(['tenant_id' => $isp->id, 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($staff)->put(route('sites.sharing'))->assertForbidden();
        $owner = User::factory()->create(['tenant_id' => $isp->id, 'role' => 'owner', 'status' => 'active']);
        $this->actingAs($owner)->put(route('sites.sharing'))->assertRedirect(route('sites.index'));
        config(['security.enforce_csp' => true]);
        $response = $this->get(route('coverage.public.map', 'network'))->assertOk()->assertHeaderMissing('X-Frame-Options');
        $this->assertStringContainsString('frame-ancestors *', $response->headers->get('Content-Security-Policy'));
    }

    public function test_homepage_links_to_self_service_guide(): void
    {
        $this->get('/api-docs/legacy')->assertOk();
        $this->get('/')->assertOk()->assertSee(route('api-docs'));
        $this->get('/api-docs?isp=north')->assertOk()->assertSee(route('coverage.public.map', 'north'))->assertSee(route('coverage.public.feed', 'north'));
    }
}
