<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantOwnedPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_queries_are_scoped_to_the_authenticated_tenant(): void
    {
        $firstTenant = $this->tenant('first');
        $secondTenant = $this->tenant('second');
        $firstPlan = Plan::factory()->create(['tenant_id' => $firstTenant->id, 'internal_name' => 'fiber']);
        Plan::factory()->create(['tenant_id' => $secondTenant->id, 'internal_name' => 'fiber']);
        $user = User::factory()->create(['tenant_id' => $firstTenant->id, 'role' => 'owner', 'status' => 'active']);

        $this->actingAs($user);

        $this->assertSame([$firstPlan->id], Plan::query()->pluck('id')->all());
    }

    private function tenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(), 'name' => Str::headline($slug), 'slug' => $slug,
            'company_name' => Str::headline($slug), 'email' => $slug.'@example.com',
            'timezone' => 'UTC', 'status' => 'active',
        ]);

    }
}
