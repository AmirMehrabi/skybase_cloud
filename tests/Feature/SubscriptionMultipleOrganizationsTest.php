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
            ->assertSee('Optionally select organizations')
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
        $this->assertSame('+1 555 0100', $subscription->phone);
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

    public function test_store_allows_a_subscription_without_selecting_organizations(): void
    {
        [$tenant, $user, $firstOrganization, , $customer] = $this->createOrganizationDependencies();
        [$plan, $router] = $this->createServiceDependencies($tenant);

        $payload = $this->subscriptionPayload($customer, $plan, $router, []);
        unset($payload['organization_ids']);

        $this->actingAs($user)
            ->postJson(route('subscriptions.store'), $payload)
            ->assertCreated();

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'organization_id' => $firstOrganization->id,
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

    public function test_store_accepts_a_customer_through_a_secondary_organization(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization, $customer] = $this->createOrganizationDependencies();
        [$plan, $router] = $this->createServiceDependencies($tenant);
        $customer->organizations()->syncWithPivotValues(
            [$firstOrganization->id, $secondOrganization->id],
            ['tenant_id' => $tenant->id],
        );

        $this->actingAs($user)
            ->postJson(route('subscriptions.store'), $this->subscriptionPayload(
                $customer,
                $plan,
                $router,
                [$secondOrganization->id],
            ))
            ->assertCreated();

        $this->assertDatabaseHas('organization_subscription', [
            'tenant_id' => $tenant->id,
            'organization_id' => $secondOrganization->id,
        ]);
    }

    public function test_selecting_a_customer_does_not_suggest_a_subscription_name(): void
    {
        [$tenant, $user, $firstOrganization, , $customer] = $this->createOrganizationDependencies();
        [$plan, $router] = $this->createServiceDependencies($tenant);

        $this->actingAs($user)
            ->get(route('subscriptions.create', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertDontSee('subscriptionNameTouched', false)
            ->assertDontSee('customerNames', false)
            ->assertSee('name="phone"', false);

        $this->actingAs($user)
            ->postJson(route('subscriptions.store'), [
                ...$this->subscriptionPayload($customer, $plan, $router, [$firstOrganization->id]),
                'name' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_subscription_phone_can_be_updated(): void
    {
        [$tenant, $user, $firstOrganization, $secondOrganization, $customer] = $this->createOrganizationDependencies();
        [$plan, $router] = $this->createServiceDependencies($tenant);

        $this->actingAs($user)
            ->postJson(route('subscriptions.store'), $this->subscriptionPayload(
                $customer,
                $plan,
                $router,
                [$firstOrganization->id, $secondOrganization->id],
            ))
            ->assertCreated();

        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('subscriptions.edit', $subscription))
            ->assertOk()
            ->assertSee('name="phone"', false)
            ->assertSee('value="+1 555 0100"', false);

        $this->actingAs($user)
            ->putJson(route('subscriptions.update', $subscription), ['phone' => '+1 555 0199'])
            ->assertOk();

        $this->assertSame('+1 555 0199', $subscription->fresh()->phone);
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
            'phone' => '+1 555 0100',
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
