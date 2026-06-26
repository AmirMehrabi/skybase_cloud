<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaxSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_default_tax_is_applied_to_subscription_items_and_invoice_snapshots(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $this->setTax($tenant, enabled: true, percentage: 15);

        $customer = $this->customer($tenant, taxExempt: false);
        $plan = Plan::factory()->create([
            'status' => 'active',
            'name' => 'Fiber 100',
            'price' => 100,
            'billing_cycle' => 'monthly',
        ]);
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post(route('subscriptions.store'), $this->subscriptionPayload($customer, $router, $plan, [
            'items' => [
                [
                    'item_type' => 'plan',
                    'description' => 'Fiber 100',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'discount_amount' => 0,
                    'discount_type' => 'none',
                    'tax_percentage' => 99,
                    'recurring' => true,
                    'billing_cycle' => 'monthly',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
        $item = $subscription->items()->firstOrFail();
        $invoice = Invoice::query()->where('subscription_id', $subscription->id)->firstOrFail();
        $invoiceItem = $invoice->items()->firstOrFail();

        $response->assertRedirect(route('subscriptions.show', $subscription));
        $this->assertEquals(15.0, (float) $item->tax_percentage);
        $this->assertEquals(15.0, (float) $item->tax_amount);
        $this->assertEquals(115.0, (float) $subscription->total_price);
        $this->assertEquals(15.0, (float) $invoiceItem->tax_percentage);
        $this->assertEquals(15.0, (float) $invoice->tax_total);
        $this->assertEquals(115.0, (float) $invoice->total);
    }

    public function test_tax_exempt_customer_gets_zero_tax(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $this->setTax($tenant, enabled: true, percentage: 15);

        $customer = $this->customer($tenant, taxExempt: true);
        $plan = Plan::factory()->create(['status' => 'active', 'price' => 100]);
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post(route('subscriptions.store'), $this->subscriptionPayload($customer, $router, $plan));
        $this->assertDatabaseHas('subscriptions', ['customer_id' => $customer->id]);

        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
        $item = $subscription->items()->firstOrFail();

        $this->assertEquals(0.0, (float) $item->tax_percentage);
        $this->assertEquals(100.0, (float) $subscription->total_price);
    }

    public function test_billing_tax_settings_can_sync_existing_subscription_items_without_changing_invoices(): void
    {
        [$tenant, $user] = $this->tenantUser();
        $this->setTax($tenant, enabled: true, percentage: 5);

        $customer = $this->customer($tenant, taxExempt: false);
        $plan = Plan::factory()->create(['status' => 'active', 'price' => 100]);
        $router = Router::factory()->online()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->post(route('subscriptions.store'), $this->subscriptionPayload($customer, $router, $plan));
        $this->assertDatabaseHas('subscriptions', ['customer_id' => $customer->id]);

        $subscription = Subscription::query()->where('customer_id', $customer->id)->firstOrFail();
        $invoice = Invoice::query()->where('subscription_id', $subscription->id)->firstOrFail();

        $response = $this->actingAs($user)->put(route('settings.update.billing-tax'), [
            'tax_enabled' => '1',
            'tax_name' => 'VAT',
            'tax_percentage' => '12',
            'show_tax_id_on_invoice' => '1',
            'invoice_note' => 'VAT is applied to taxable services.',
            'sync_existing_subscription_items' => '1',
        ]);

        $response->assertRedirect(route('settings.index', ['tab' => 'billing']));
        $response->assertSessionHasNoErrors();

        $subscription->refresh();
        $syncedItem = $subscription->items()->firstOrFail();

        $this->assertEquals(12.0, (float) $syncedItem->tax_percentage);
        $this->assertEquals(112.0, (float) $subscription->total_price);
        $this->assertEquals(5.0, (float) $invoice->fresh()->tax_total);
        $this->assertEquals(105.0, (float) $invoice->fresh()->total);
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

    private function setTax(Tenant $tenant, bool $enabled, float $percentage): void
    {
        Setting::create([
            'tenant_id' => $tenant->id,
            'key' => 'billing.tax',
            'value' => [
                'enabled' => $enabled,
                'name' => 'VAT',
                'percentage' => $percentage,
                'show_tax_id_on_invoice' => false,
                'invoice_note' => null,
            ],
            'type' => 'json',
            'group' => 'billing',
        ]);
    }

    private function customer(Tenant $tenant, bool $taxExempt): Customer
    {
        return Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => Str::random(8).'@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => $taxExempt,
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
            'name' => 'Taxed Service',
            'service_type' => 'pppoe',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => 'customer-'.$customer->id,
            'pppoe_password' => 'secret-pass',
            'billing_cycle' => 'monthly',
            'billing_enabled' => '1',
            'status' => 'active',
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
