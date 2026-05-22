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

class OrganizationBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_create_rejects_service_that_differs_from_billing_enabled_organization_default(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $defaultPlan = Plan::factory()->create(['status' => 'active', 'price' => 80, 'billing_cycle' => 'monthly']);
        $otherPlan = Plan::factory()->create(['status' => 'active', 'price' => 120, 'billing_cycle' => 'monthly']);
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);
        $organization = $this->organization($tenant, $defaultPlan);
        $customer = $this->customer($tenant, $organization);

        $response = $this->actingAs($user)
            ->from(route('subscriptions.create'))
            ->post(route('subscriptions.store'), $this->subscriptionPayload($customer, $router, $otherPlan));

        $response->assertRedirect(route('subscriptions.create'));
        $response->assertSessionHasErrors('plan_id');
        $this->assertDatabaseMissing('subscriptions', [
            'customer_id' => $customer->id,
            'plan_id' => $otherPlan->id,
        ]);
    }

    public function test_subscription_create_applies_organization_billing_defaults_to_subscription_and_plan_item(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $defaultPlan = Plan::factory()->create(['status' => 'active', 'name' => 'Managed Fiber', 'price' => 100, 'billing_cycle' => 'monthly']);
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);
        $organization = $this->organization($tenant, $defaultPlan);
        $customer = $this->customer($tenant, $organization);

        $response = $this->actingAs($user)->post(route('subscriptions.store'), $this->subscriptionPayload($customer, $router, $defaultPlan, [
            'billing_cycle' => 'yearly',
            'grace_period_days' => 99,
            'items' => [
                [
                    'item_type' => 'plan',
                    'description' => 'Manual Item',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'discount_amount' => 0,
                    'discount_type' => 'none',
                    'tax_percentage' => 0,
                    'recurring' => true,
                    'billing_cycle' => 'yearly',
                ],
            ],
        ]));

        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
        $item = $subscription->items()->where('item_type', 'plan')->firstOrFail();

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $response->assertSessionHasNoErrors();

        $this->assertSame($defaultPlan->id, $subscription->plan_id);
        $this->assertSame('quarterly', $subscription->billing_cycle);
        $this->assertSame(12, $subscription->grace_period_days);
        $this->assertSame('Managed Fiber', $item->description);
        $this->assertSame('percentage', $item->discount_type);
        $this->assertEquals(10.0, (float) $item->discount_amount);
        $this->assertEquals(8.0, (float) $item->tax_percentage);
        $this->assertEquals(97.2, (float) $item->total);
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

    private function organization(Tenant $tenant, Plan $plan): Organization
    {
        return Organization::factory()->create([
            'tenant_id' => $tenant->id,
            'billing_enabled' => true,
            'billing_disabled_at' => null,
            'default_plan_id' => $plan->id,
            'default_billing_cycle' => 'quarterly',
            'default_grace_period_days' => 12,
            'default_discount_type' => 'percentage',
            'default_discount_amount' => 10,
            'default_tax_percentage' => 8,
        ]);
    }

    private function customer(Tenant $tenant, Organization $organization): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'status' => 'active',
            'billing_enabled' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function subscriptionPayload(Customer $customer, Router $router, Plan $plan, array $overrides = []): array
    {
        return [
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'customer-'.$customer->id,
            'pppoe_password' => 'secret-pass',
            'billing_cycle' => 'monthly',
            'billing_enabled' => '1',
            'status' => 'pending',
            'items' => [
                [
                    'item_type' => 'plan',
                    'description' => $plan->name,
                    'quantity' => 1,
                    'unit_price' => $plan->price,
                    'discount_amount' => 0,
                    'discount_type' => 'none',
                    'tax_percentage' => 0,
                    'recurring' => true,
                    'billing_cycle' => 'monthly',
                ],
            ],
            ...$overrides,
        ];
    }
}
