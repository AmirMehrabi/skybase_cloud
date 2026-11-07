<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RouterIpUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_can_be_created_with_another_tenants_ip_address(): void
    {
        $admin = $this->createAdmin();
        $this->createRouter($this->createTenant(), '10.8.0.3');

        $this->actingAs($admin)->postJson(route('routers.store'), $this->payload('10.8.0.3'))
            ->assertCreated();

        $this->assertDatabaseHas('routers', ['tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.3']);
    }

    public function test_router_can_be_updated_to_another_tenants_ip_address(): void
    {
        $admin = $this->createAdmin();
        $router = Router::factory()->create(['tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.4']);
        $this->createRouter($this->createTenant(), '10.8.0.3');

        $this->actingAs($admin)->putJson(route('routers.update', $router), $this->payload('10.8.0.3'))
            ->assertOk();

        $this->assertDatabaseHas('routers', ['id' => $router->id, 'tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.3']);
    }

    public function test_router_can_be_updated_without_changing_its_ip_address(): void
    {
        $admin = $this->createAdmin();
        $router = Router::factory()->create(['tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.3']);

        $this->actingAs($admin)->putJson(route('routers.update', $router), $this->payload('10.8.0.3'))
            ->assertOk();
    }

    public function test_duplicate_ip_is_rejected_on_create_within_the_same_tenant(): void
    {
        $admin = $this->createAdmin();
        Router::factory()->create(['tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.3']);

        $this->actingAs($admin)->postJson(route('routers.store'), $this->payload('10.8.0.3'))
            ->assertUnprocessable()->assertJsonValidationErrors('ip_address');
    }

    public function test_duplicate_ip_is_rejected_on_update_within_the_same_tenant(): void
    {
        $admin = $this->createAdmin();
        Router::factory()->create(['tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.3']);
        $router = Router::factory()->create(['tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.4']);

        $this->actingAs($admin)->putJson(route('routers.update', $router), $this->payload('10.8.0.3'))
            ->assertUnprocessable()->assertJsonValidationErrors('ip_address');

        $this->assertDatabaseHas('routers', ['id' => $router->id, 'tenant_id' => $admin->tenant_id, 'ip_address' => '10.8.0.4']);
    }

    public function test_database_rejects_duplicate_ip_addresses_within_a_tenant(): void
    {
        $tenant = $this->createTenant();
        $this->createRouter($tenant, '10.8.0.3');

        $this->expectException(UniqueConstraintViolationException::class);

        $this->createRouter($tenant, '10.8.0.3');
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => $this->createTenant()->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function createTenant(): Tenant
    {
        $slug = (string) Str::uuid();

        return Tenant::create([
            'id' => $slug,
            'name' => $slug,
            'slug' => $slug,
            'company_name' => $slug,
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function createRouter(Tenant $tenant, string $ipAddress): Router
    {
        return Router::factory()->create(['tenant_id' => $tenant->id, 'ip_address' => $ipAddress]);
    }

    /**
     * @return array<string, int|string>
     */
    private function payload(string $ipAddress): array
    {
        return [
            'name' => 'Tenant Router',
            'vendor' => 'Mikrotik',
            'ip_address' => $ipAddress,
            'api_port' => 8729,
            'ssh_port' => 22,
        ];
    }
}
