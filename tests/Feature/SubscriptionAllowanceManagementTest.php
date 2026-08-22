<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionDataAdjustment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubscriptionUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionAllowanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_renders_compact_management_for_a_capped_plan(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => 10,
            'data_unit' => 'GB',
            'unlimited' => false,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.show', $subscription));

        $response->assertOk()
            ->assertSee('Data Allowance')
            ->assertSee('Capped')
            ->assertSee('Awaiting first usage reconciliation')
            ->assertSee('Grant bonus data')
            ->assertDontSee('Cycle data allowance')
            ->assertDontSee('action="'.route('subscriptions.usage.reset', $subscription).'"', false);
    }

    public function test_show_page_hides_allowance_actions_for_an_unlimited_plan(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => null,
            'unlimited' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.show', $subscription));

        $response->assertOk()
            ->assertSee('Unlimited data')
            ->assertSee('This plan has no cycle data cap.')
            ->assertDontSee('Grant bonus data')
            ->assertDontSee('Apply exemption');
    }

    public function test_show_page_exposes_reset_when_the_current_cycle_has_usage(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => 10,
            'data_unit' => 'GB',
            'unlimited' => false,
        ]);
        $cycle = app(SubscriptionUsageService::class)->currentCycle($subscription);
        $cycle->update(['used_download_bytes' => 2147483648]);

        $response = $this->actingAs($user)->get(route('subscriptions.show', $subscription));

        $response->assertOk()
            ->assertSee('2.00 GB used')
            ->assertSee('8.00 GB remaining')
            ->assertSee('action="'.route('subscriptions.usage.reset', $subscription).'"', false);
    }

    public function test_show_page_explains_an_unconfigured_plan_without_actions(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => null,
            'unlimited' => false,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.show', $subscription));

        $response->assertOk()
            ->assertSee('Not configured')
            ->assertSee('This plan does not have a data allowance configured.')
            ->assertDontSee('Grant bonus data');
    }

    public function test_allowance_actions_reject_plans_without_a_finite_allowance(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => null,
            'unlimited' => true,
        ]);

        $response = $this->actingAs($user)->from(route('subscriptions.show', $subscription))->post(
            route('subscriptions.usage.bonus', $subscription),
            [
                'allowance_action' => 'bonus',
                'amount' => 5,
                'unit' => 'GB',
                'reason' => 'Retention credit',
            ],
        );

        $response->assertRedirect(route('subscriptions.show', $subscription))
            ->assertSessionHasErrors('data_allowance');
        $this->assertDatabaseCount('subscription_usage_cycles', 0);
        $this->assertDatabaseCount('subscription_data_adjustments', 0);
    }

    public function test_reset_rejects_a_capped_plan_without_recorded_cycle_usage(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => 10,
            'data_unit' => 'GB',
            'unlimited' => false,
        ]);

        $response = $this->actingAs($user)->from(route('subscriptions.show', $subscription))->post(
            route('subscriptions.usage.reset', $subscription),
            [
                'allowance_action' => 'reset',
                'reason' => 'Operator correction',
            ],
        );

        $response->assertRedirect(route('subscriptions.show', $subscription))
            ->assertSessionHasErrors('data_allowance');
        $this->assertDatabaseCount('subscription_usage_cycles', 0);
        $this->assertDatabaseCount('subscription_data_adjustments', 0);
    }

    public function test_bonus_data_is_granted_for_a_capped_plan(): void
    {
        [$user, $subscription] = $this->subscriptionWithPlan([
            'data_limit' => 10,
            'data_unit' => 'GB',
            'unlimited' => false,
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.usage.bonus', $subscription), [
            'allowance_action' => 'bonus',
            'amount' => 2,
            'unit' => 'GB',
            'reason' => 'Service recovery',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $adjustment = SubscriptionDataAdjustment::query()->firstOrFail();
        $this->assertSame(2147483648, $adjustment->bytes);
        $this->assertSame('Service recovery', $adjustment->reason);
    }

    /**
     * @param  array<string, mixed>  $planAttributes
     * @return array{0: User, 1: Subscription}
     */
    private function subscriptionWithPlan(array $planAttributes): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Allowance ISP',
            'slug' => 'allowance-'.Str::lower(Str::random(8)),
            'company_name' => 'Allowance ISP',
            'email' => Str::lower(Str::random(8)).'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'name' => 'Allowance Customer',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'status' => 'active',
        ]);
        $plan = Plan::factory()->create([
            ...$planAttributes,
            'tenant_id' => $tenant->id,
            'status' => 'active',
            'type' => 'pppoe',
            'billing_cycle' => 'monthly',
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(8)),
            'connection_type' => 'pppoe',
            'pppoe_username' => 'allowance.'.Str::lower(Str::random(8)),
            'pppoe_password' => 'secret',
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now()->startOfMonth(),
        ]);

        return [$user, $subscription];
    }
}
