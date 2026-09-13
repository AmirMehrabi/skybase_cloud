<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiteCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_displays_sites_module(): void
    {
        [$tenant, $user] = $this->tenantUser();

        Site::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'North Tower',
            'code' => 'NORTH',
        ]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk()
            ->assertSee('Sites')
            ->assertSee('Topology Map');
    }

    public function test_can_create_update_and_delete_site(): void
    {
        [$tenant, $user] = $this->tenantUser();

        $createResponse = $this->actingAs($user)->post(route('sites.store'), [
            'name' => 'Central POP',
            'code' => 'CENTRAL',
            'address' => 'Central Office',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'status' => 'active',
            'description' => 'Core aggregation point.',
        ]);

        $site = Site::query()->where('code', 'CENTRAL')->first();

        $createResponse->assertRedirect(route('sites.show', $site));
        $this->assertDatabaseHas('sites', [
            'tenant_id' => $tenant->id,
            'code' => 'CENTRAL',
            'name' => 'Central POP',
        ]);

        $updateResponse = $this->actingAs($user)->put(route('sites.update', $site), [
            'name' => 'Central POP Updated',
            'code' => 'CENTRAL',
            'address' => 'Updated Office',
            'latitude' => 35.7001,
            'longitude' => 51.4012,
            'status' => 'inactive',
            'description' => null,
        ]);

        $updateResponse->assertRedirect(route('sites.show', $site));
        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Central POP Updated',
            'status' => 'inactive',
        ]);

        $deleteResponse = $this->actingAs($user)->delete(route('sites.destroy', $site));

        $deleteResponse->assertRedirect(route('sites.index'));
        $this->assertDatabaseMissing('sites', [
            'id' => $site->id,
        ]);
    }

    public function test_site_validation_rejects_duplicate_code_and_invalid_coordinates(): void
    {
        [$tenant, $user] = $this->tenantUser();

        Site::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'DUPLICATE',
        ]);

        $response = $this->actingAs($user)->post(route('sites.store'), [
            'name' => 'Invalid Site',
            'code' => 'DUPLICATE',
            'latitude' => 91,
            'longitude' => 181,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['code', 'latitude', 'longitude']);
    }

    public function test_owner_can_assign_multiple_user_groups_to_a_site(): void
    {
        [$tenant, $owner] = $this->tenantUser('owner-net', 'owner');
        $firstGroup = UserGroup::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'North Team',
        ]);
        $secondGroup = UserGroup::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'South Team',
        ]);

        $this->actingAs($owner)
            ->get(route('sites.create'))
            ->assertOk()
            ->assertSee('User Groups')
            ->assertSee('name="user_group_ids[]"', false);

        $this->actingAs($owner)
            ->post(route('sites.store'), [
                ...$this->sitePayload('MULTI', 'Multi Group Site'),
                'user_group_ids' => [$firstGroup->id, $secondGroup->id],
            ])
            ->assertSessionHasNoErrors();

        $site = Site::withoutGlobalScopes()->where('code', 'MULTI')->firstOrFail();
        $this->assertSame($firstGroup->id, $site->user_group_id);
        $this->assertDatabaseHas('site_user_group', [
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'user_group_id' => $firstGroup->id,
        ]);
        $this->assertDatabaseHas('site_user_group', [
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'user_group_id' => $secondGroup->id,
        ]);

        $this->actingAs($owner)
            ->put(route('sites.update', $site), [
                ...$this->sitePayload('MULTI', 'Updated Multi Group Site'),
                'user_group_ids' => [$secondGroup->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('site_user_group', [
            'site_id' => $site->id,
            'user_group_id' => $firstGroup->id,
        ]);
        $this->assertDatabaseHas('site_user_group', [
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'user_group_id' => $secondGroup->id,
        ]);
        $this->assertSame($secondGroup->id, $site->fresh()->user_group_id);
    }

    public function test_site_rejects_a_user_group_from_another_tenant(): void
    {
        [, $owner] = $this->tenantUser('owner-net', 'owner');
        $otherTenant = $this->createTenant('other-net');
        $otherGroup = UserGroup::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Team',
        ]);

        $this->actingAs($owner)
            ->post(route('sites.store'), [
                ...$this->sitePayload('INVALID-GROUP', 'Invalid Group Site'),
                'user_group_ids' => [$otherGroup->id],
            ])
            ->assertSessionHasErrors('user_group_ids.0');

        $this->assertDatabaseMissing('sites', ['code' => 'INVALID-GROUP']);
    }

    public function test_map_data_is_tenant_scoped_and_contains_router_health(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $otherTenant = $this->createTenant('beta-net');

        $site = Site::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Mapped Site',
            'code' => 'MAPPED',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
        ]);

        Router::factory()->create([
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'status' => 'online',
        ]);
        Router::factory()->create([
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'status' => 'offline',
        ]);

        Site::factory()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Site',
            'code' => 'OTHER',
        ]);

        $response = $this->actingAs($user)->getJson(route('sites.map-data'));

        $response->assertOk()
            ->assertJsonPath('sites.0.name', 'Mapped Site')
            ->assertJsonPath('sites.0.routers_count', 2)
            ->assertJsonPath('sites.0.online_routers_count', 1)
            ->assertJsonPath('sites.0.offline_routers_count', 1)
            ->assertJsonPath('sites.0.health', 'degraded')
            ->assertJsonMissing(['name' => 'Other Tenant Site']);
    }

    public function test_router_site_assignment_must_belong_to_current_tenant(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $otherTenant = $this->createTenant('beta-net');
        $otherSite = Site::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($user)->post(route('routers.store'), [
            'name' => 'Edge Router',
            'vendor' => 'Mikrotik',
            'model' => 'CCR',
            'ip_address' => '192.0.2.10',
            'api_port' => 8728,
            'ssh_port' => 22,
            'site_id' => $otherSite->id,
        ]);

        $response->assertSessionHasErrors(['site_id']);

        $site = Site::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $validResponse = $this->actingAs($user)->post(route('routers.store'), [
            'name' => 'Valid Edge Router',
            'vendor' => 'Mikrotik',
            'model' => 'CCR',
            'ip_address' => '192.0.2.11',
            'api_port' => 8728,
            'ssh_port' => 22,
            'site_id' => $site->id,
        ]);

        $validResponse->assertRedirect(route('routers.index'));
        $this->assertDatabaseHas('routers', [
            'name' => 'Valid Edge Router',
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
        ]);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function tenantUser(string $slug = 'alpha-net', string $role = 'admin'): array
    {
        $tenant = $this->createTenant($slug);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
        ]);

        return [$tenant, $user];
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => "{$slug}@example.com",
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /** @return array<string, mixed> */
    private function sitePayload(string $code, string $name): array
    {
        return [
            'name' => $name,
            'code' => $code,
            'address' => 'Main Office',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'status' => 'active',
            'description' => null,
        ];
    }
}
