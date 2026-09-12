<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionMultipleOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_provides_organizations_and_customers_for_client_side_filtering(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization, $customer] = $this->createOrganizationDependencies();
        $secondCustomer = Customer::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $secondOrganization->id,
            'customer_code' => 'CUST-MULTI-002',
            'customer_type' => 'individual',
            'first_name' => 'Second',
            'last_name' => 'Customer',
            'name' => 'Second Organization Customer',
            'email' => 'second-customer@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('subscriptions.create'))
            ->assertOk()
            ->assertViewHas('organizations', fn ($organizations): bool => $organizations->pluck('id')->contains($firstOrganization->id)
                && $organizations->pluck('id')->contains($secondOrganization->id))
            ->assertViewHas('customers', fn ($customers): bool => $customers->pluck('id')->contains($customer->id)
                && $customers->pluck('id')->contains($secondCustomer->id))
            ->assertSee('Select one or more organizations')
            ->assertSee('filteredCustomers');
    }

    public function test_store_attaches_multiple_tenant_organizations_to_a_subscription(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization, $customer] = $this->createOrganizationDependencies();
        [$plan, $router] = $this->createServiceDependencies($tenant);

        $response = $this->actingAs($user)->postJson(route('subscriptions.store'), $this->subscriptionPayload(
            $customer,
            $plan,
            $router,
            [$firstOrganization->id, $secondOrganization->id],
        ));

        $response->assertCreated();

        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame($firstOrganization->id, $subscription->organization_id);
        $this->assertEqualsCanonicalizing(
            [$firstOrganization->id, $secondOrganization->id],
            $subscription->organizations()->pluck('organizations.id')->all(),
        );
        $this->assertDatabaseHas('organization_subscription', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'organization_id' => $secondOrganization->id,
        ]);
    }

    public function test_store_rejects_a_customer_outside_the_selected_organizations(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization, $customer] = $this->createOrganizationDependencies();
        [$plan, $router] = $this->createServiceDependencies($tenant);

        $this->actingAs($user)
            ->postJson(route('subscriptions.store'), $this->subscriptionPayload(
                $customer,
                $plan,
                $router,
                [$secondOrganization->id],
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id'])
            ->assertJsonPath('errors.customer_id.0', 'The selected customer must belong to one of the selected organizations.');

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseMissing('organization_subscription', [
            'tenant_id' => $tenant->id,
            'organization_id' => $firstOrganization->id,
        ]);
    }

    /**
     * @return array{Tenant, User, Organization, Organization, Customer}
     */
    private function createOrganizationDependencies(): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Multiple Organizations',
            'slug' => 'multiple-organizations',
            'company_name' => 'Multiple Organizations',
            'email' => 'multiple-organizations@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $firstOrganization = Organization::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'First Organization',
            'status' => 'active',
            'billing_enabled' => false,
        ]);
        $secondOrganization = Organization::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Second Organization',
            'status' => 'active',
            'billing_enabled' => false,
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $firstOrganization->id,
            'customer_code' => 'CUST-MULTI-001',
            'customer_type' => 'individual',
            'first_name' => 'First',
            'last_name' => 'Customer',
            'name' => 'First Organization Customer',
            'email' => 'first-customer@example.com',
            'status' => 'active',
            'billing_enabled' => true,
        ]);

        return [$tenant, $user, $firstOrganization, $secondOrganization, $customer];
    }

    /**
     * @return array{Plan, Router}
     */
    private function createServiceDependencies(Tenant $tenant): array
    {
        $plan = Plan::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
            'price' => 50,
            'billing_cycle' => 'monthly',
        ]);
        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
        ]);

        return [$plan, $router];
    }

    /**
     * @param  list<int>  $organizationIds
     * @return array<string, mixed>
     */
    private function subscriptionPayload(Customer $customer, Plan $plan, Router $router, array $organizationIds): array
    {
        return [
            'organization_ids' => $organizationIds,
            'customer_id' => $customer->id,
            'name' => 'Shared Organization Service',
            'service_type' => 'hotspot',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'static',
            'billing_enabled' => true,
            'status' => 'active',
            'items' => [[
                'item_type' => 'plan',
                'description' => $plan->name,
                'quantity' => 1,
                'unit_price' => 50,
                'discount_amount' => 0,
                'discount_type' => 'none',
                'tax_percentage' => 0,
                'recurring' => true,
                'billing_cycle' => 'monthly',
            ]],
        ];
    }
}
