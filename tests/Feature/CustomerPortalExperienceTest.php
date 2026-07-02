<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Monitoring\CustomerBandwidthUsageService;
use App\Services\Monitoring\RrdToolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class CustomerPortalExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_owned_subscription_and_not_another_customers_subscription(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant, 'owner@example.com');
        $otherCustomer = $this->createCustomer($tenant, 'other@example.com');
        $ownedSubscription = $this->createSubscription($tenant, $customer, 'SUB-OWNED');
        $otherSubscription = $this->createSubscription($tenant, $otherCustomer, 'SUB-OTHER');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.subscriptions.index'))
            ->assertOk()
            ->assertSee(route('customer.subscriptions.show', $ownedSubscription), false)
            ->assertSee('View subscription SUB-OWNED');

        $this->get(route('customer.subscriptions.show', $ownedSubscription))
            ->assertOk()
            ->assertSee('SUB-OWNED')
            ->assertSee('Subscription usage');

        $this->get(route('customer.subscriptions.show', $otherSubscription))
            ->assertNotFound();
    }

    public function test_customer_profile_changes_password_with_the_current_password(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant, 'owner@example.com');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.profile.show'))
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('owner@example.com');

        $this->patch(route('customer.profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->patch(route('customer.profile.password.update'), [
            'current_password' => 'password123',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('customer.profile.show'));

        $this->assertTrue(Hash::check('new-password-123', $customer->fresh()->password));
    }

    public function test_customer_dashboard_uses_real_subscription_counts_and_usage_endpoint(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant, 'owner@example.com');
        $this->createSubscription($tenant, $customer, 'SUB-A', ['connection_status' => 'online']);
        $this->createSubscription($tenant, $customer, 'SUB-B', ['status' => 'suspended']);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('2 total')
            ->assertSee('Total subscription usage')
            ->assertDontSee('284 GB');

        $this->getJson(route('customer.dashboard.usage'))
            ->assertOk()
            ->assertJsonPath('range', '24h')
            ->assertJsonStructure(['chartData', 'hasData']);
    }

    public function test_customer_bandwidth_service_sums_subscription_series_and_preserves_missing_values(): void
    {
        $tenant = $this->createTenant();
        $customer = $this->createCustomer($tenant, 'owner@example.com');
        $first = $this->createSubscription($tenant, $customer, 'SUB-A');
        $second = $this->createSubscription($tenant, $customer, 'SUB-B');

        $rrdTool = Mockery::mock(RrdToolService::class);
        $rrdTool->shouldReceive('subscriptionBandwidthChartData')
            ->with($first, '24h')
            ->once()
            ->andReturn([
                'chartData' => [
                    ['timestamp' => 100, 'time' => '00:00', 'rx_bps' => 10.0, 'tx_bps' => null],
                ],
                'hasData' => true,
            ]);
        $rrdTool->shouldReceive('subscriptionBandwidthChartData')
            ->with($second, '24h')
            ->once()
            ->andReturn([
                'chartData' => [
                    ['timestamp' => 100, 'time' => '00:00', 'rx_bps' => 20.0, 'tx_bps' => 5.0],
                    ['timestamp' => 200, 'time' => '00:05', 'rx_bps' => null, 'tx_bps' => null],
                ],
                'hasData' => true,
            ]);

        $result = (new CustomerBandwidthUsageService($rrdTool))
            ->aggregate(new Collection([$first, $second]), '24h');

        $this->assertTrue($result['hasData']);
        $this->assertSame(30.0, $result['chartData'][0]['rx_bps']);
        $this->assertSame(5.0, $result['chartData'][0]['tx_bps']);
        $this->assertSame(35.0, $result['chartData'][0]['total_bps']);
        $this->assertNull($result['chartData'][1]['total_bps']);
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Alpha Net',
            'slug' => 'alpha-net',
            'company_name' => 'Alpha Net',
            'email' => 'alpha@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    private function createCustomer(Tenant $tenant, string $email): Customer
    {
        return Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_code' => Customer::generateCustomerCode(),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => $email,
            'mobile' => '555-0101',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'password' => 'password123',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSubscription(
        Tenant $tenant,
        Customer $customer,
        string $code,
        array $overrides = []
    ): Subscription {
        $plan = Plan::factory()->create(['name' => 'Fiber 100']);

        return Subscription::create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => $code,
            'name' => $code,
            'plan_id' => $plan->id,
            'service_type' => 'internet',
            'connection_type' => 'dhcp',
            'base_price' => 100,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 100,
            'billing_cycle' => 'monthly',
            'billing_enabled' => true,
            'status' => 'active',
            'start_date' => now(),
        ], $overrides));
    }
}
