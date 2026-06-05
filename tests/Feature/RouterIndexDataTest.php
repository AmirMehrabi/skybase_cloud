<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RouterIndexDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_index_data_defaults_to_fifty_items_per_page(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        for ($index = 1; $index <= 55; $index++) {
            Router::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => sprintf('Router %02d', $index),
                'ip_address' => '10.0.0.'.$index,
            ]);
        }

        $response = $this->actingAs($admin)->getJson(route('routers.data'));

        $response->assertOk();
        $this->assertCount(50, $response->json('routers'));
        $this->assertSame(1, $response->json('pagination.current_page'));
        $this->assertSame(50, $response->json('pagination.per_page'));
        $this->assertSame(55, $response->json('pagination.total'));
        $this->assertSame(1, $response->json('pagination.from'));
        $this->assertSame(50, $response->json('pagination.to'));
    }

    public function test_router_index_data_returns_the_second_page_when_requested(): void
    {
        $tenant = $this->createTenant('alpha-net');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        for ($index = 1; $index <= 55; $index++) {
            Router::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => sprintf('Router %02d', $index),
                'ip_address' => '10.0.1.'.$index,
            ]);
        }

        $response = $this->actingAs($admin)->getJson(route('routers.data', ['page' => 2]));

        $response->assertOk();
        $this->assertCount(5, $response->json('routers'));
        $this->assertSame(2, $response->json('pagination.current_page'));
        $this->assertSame(50, $response->json('pagination.per_page'));
        $this->assertSame(55, $response->json('pagination.total'));
        $this->assertSame(51, $response->json('pagination.from'));
        $this->assertSame(55, $response->json('pagination.to'));
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
