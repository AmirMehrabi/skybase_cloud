<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_an_organization_with_billing_defaults(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $plan = Plan::factory()->create(['status' => 'active', 'billing_cycle' => 'monthly']);

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Enterprise Accounts',
            'code' => 'ENT',
            'status' => 'active',
            'billing_enabled' => '1',
            'default_plan_id' => $plan->id,
            'default_billing_cycle' => 'monthly',
            'default_grace_period_days' => '10',
            'default_discount_type' => 'percentage',
            'default_discount_amount' => '5',
            'default_tax_percentage' => '9',
        ]);

        $organization = Organization::query()->firstOrFail();

        $response->assertRedirect(route('organizations.show', $organization));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('organizations', [
            'tenant_id' => $tenant->id,
            'name' => 'Enterprise Accounts',
            'code' => 'ENT',
            'billing_enabled' => true,
            'default_plan_id' => $plan->id,
        ]);
    }

    public function test_organization_cannot_be_deleted_while_customers_are_assigned(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $organization = Organization::factory()->create([
            'tenant_id' => $tenant->id,
            'billing_enabled' => false,
        ]);
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete(route('organizations.destroy', $organization));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('organizations', ['id' => $organization->id]);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function tenantUser(): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'AlphaNet Communications',
            'slug' => 'alpha-net',
            'company_name' => 'AlphaNet Communications',
            'email' => 'alpha@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        return [$tenant, $user];
    }
}
