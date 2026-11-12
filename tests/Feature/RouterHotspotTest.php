<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RouterOs\HotspotPortal;
use App\Services\RouterOs\RouterHotspot;
use App\Services\RouterOs\RouterOsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RouterHotspotTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_renders_the_tenant_name_and_logo(): void
    {
        [$tenant, $admin, $router] = $this->fixture();
        Storage::fake('public')->put('brand.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jKOcAAAAASUVORK5CYII='));
        $tenant->update(['company_logo' => 'brand.png']);

        $this->actingAs($admin)->get(route('routers.hotspot.show', $router))
            ->assertOk()
            ->assertSee('Fibre House')
            ->assertSee('data:image')
            ->assertSee('Sync from RouterOS');
    }

    public function test_sync_reads_routeros_and_stale_profile_changes_are_rejected(): void
    {
        [, $admin, $router] = $this->fixture();
        $client = $this->mock(RouterOsClient::class);
        $client->shouldReceive('execute')->twice()->andReturnUsing(fn (Router $target, callable $operation): array => $operation(null));
        $client->shouldReceive('writeSentence')->with(null, \Mockery::on(fn (array $words): bool => str_ends_with($words[0], '/print')))->times(4);
        $client->shouldReceive('readResponse')->with(null)->andReturn(
            [['.id' => '*1', 'name' => 'Lobby', 'login-by' => 'http-chap']],
            [['.id' => '*2', 'name' => 'Guest', 'interface' => 'bridge-guest', 'profile' => 'Lobby']],
            [['.id' => '*1', 'name' => 'Renamed in WinBox', 'login-by' => 'http-chap']],
            [],
        );

        $this->actingAs($admin);
        $sync = $this->getJson(route('routers.hotspot.sync', $router))
            ->assertOk()->assertJsonPath('profiles.0.networks.0.interface', 'bridge-guest');

        $this->postJson(route('routers.hotspot.mutate', $router), [
            'action' => 'update',
            'profile' => '*1',
            'revision' => $sync->json('profiles.0.revision'),
            'settings' => ['name' => 'Lobby New', 'login-by' => ['http-chap']],
        ])->assertUnprocessable()->assertJsonValidationErrors('profile');
    }

    public function test_publish_records_the_rollback_target_before_switching_routeros(): void
    {
        [, $admin, $router] = $this->fixture();
        $profile = ['.id' => '*1', 'name' => 'Guest', 'login-by' => 'http-chap', 'html-directory' => '/flash/hotspot', 'networks' => []];
        $revision = str_repeat('a', 64);
        $design = ['headline' => 'Welcome', 'message' => 'Sign in', 'button' => 'Connect', 'accent' => '#167456', 'layout' => 'light'];
        $hotspot = $this->mock(RouterHotspot::class);
        $hotspot->shouldReceive('state')->once()->andReturn([]);
        $hotspot->shouldReceive('current')->once()->withArgs(fn (Router $target, string $id, string $hash): bool => $target->is($router) && $id === '*1' && $hash === $revision)->andReturn($profile);
        $hotspot->shouldReceive('directory')->once()->with($profile)->andReturn('/flash/hotspot');
        $hotspot->shouldReceive('hasFlash')->once()->andReturn(true);
        $portal = $this->mock(HotspotPortal::class);
        $portal->shouldReceive('render')->once()->andReturn('<html>Welcome</html>');
        $portal->shouldReceive('prepare')->once()->withArgs(fn (Router $target, string $source, bool $flash, bool $chap, string $html, bool $upload): bool => $target->is($router) && $source === '/flash/hotspot' && $flash && $chap && $upload)->andReturn('/flash/skybase-release');
        $hotspot->shouldReceive('save')->once()->ordered()->withArgs(fn (Router $target, array $state): bool => $state['profiles']['*1']['releases']['flash/skybase-release']['previous']['paths']['html-directory'] === '/flash/hotspot');
        $hotspot->shouldReceive('activate')->once()->ordered()->withArgs(fn (Router $target, array $current, array $paths): bool => $current === $profile && $paths === ['html-directory' => '/flash/skybase-release']);

        $this->actingAs($admin)->postJson(route('routers.hotspot.mutate', $router), [
            'action' => 'publish',
            'profile' => '*1',
            'revision' => $revision,
            'design' => $design,
        ])->assertOk();
    }

    public function test_other_tenant_cannot_sync_a_router(): void
    {
        [, , $router] = $this->fixture();
        [, $otherAdmin] = $this->fixture();
        $this->mock(RouterOsClient::class)->shouldNotReceive('execute');

        $this->actingAs($otherAdmin)->getJson(route('routers.hotspot.sync', $router))->assertNotFound();
    }

    private function fixture(): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Fibre House',
            'company_name' => 'Fibre House',
            'slug' => (string) Str::ulid(),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
        ]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'status' => 'active']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'vendor' => 'Mikrotik']);

        return [$tenant, $admin, $router];
    }
}
