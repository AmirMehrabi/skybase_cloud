<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use App\Services\SubscriptionRestrictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingAutoSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_subscription_is_suspended_when_auto_suspension_is_enabled(): void
    {
        [$tenant, $customer] = $this->createTenantContext('auto-enabled');
        $subscription = $this->createSubscription($tenant, $customer, true);
        $invoice = $this->createOverdueInvoice($tenant, $customer, $subscription);

        $results = app(BillingService::class)->run(today());

        $this->assertSame(1, $results['marked_overdue']);
        $this->assertSame(1, $results['suspended_subscriptions']);
        $this->assertSame('overdue', $invoice->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertTrue($subscription->restrictions()->active()->where('type', 'billing')->exists());
    }

    public function test_overdue_invoice_is_marked_without_suspending_when_auto_suspension_is_disabled(): void
    {
        [$tenant, $customer] = $this->createTenantContext('auto-disabled');
        $subscription = $this->createSubscription($tenant, $customer, false);
        $invoice = $this->createOverdueInvoice($tenant, $customer, $subscription);

        $results = app(BillingService::class)->run(today());

        $this->assertSame(1, $results['marked_overdue']);
        $this->assertSame(0, $results['suspended_subscriptions']);
        $this->assertSame('overdue', $invoice->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_disabling_billing_always_disables_auto_suspension(): void
    {
        [$tenant, $customer] = $this->createTenantContext('billing-disabled');
        $subscription = $this->createSubscription($tenant, $customer, true);

        $subscription->update([
            'billing_enabled' => false,
            'auto_suspension_enabled' => true,
        ]);

        $this->assertFalse($subscription->fresh()->billing_enabled);
        $this->assertFalse($subscription->fresh()->auto_suspension_enabled);
    }

    public function test_full_payment_immediately_clears_only_the_billing_restriction(): void
    {
        [$tenant, $customer] = $this->createTenantContext('payment-restoration');
        $subscription = $this->createSubscription($tenant, $customer, true);
        $invoice = $this->createOverdueInvoice($tenant, $customer, $subscription);
        $restrictions = app(SubscriptionRestrictionService::class);
        $restrictions->restrict($subscription, 'billing', 'Past grace');
        $restrictions->restrict($subscription, 'manual', 'Operator hold');

        $invoice->update(['paid_amount' => 100, 'balance_due' => 0, 'status' => 'paid']);
        app(BillingService::class)->reconcileSubscriptionAfterPayment($subscription);

        $this->assertFalse($subscription->restrictions()->active()->where('type', 'billing')->exists());
        $this->assertTrue($subscription->restrictions()->active()->where('type', 'manual')->exists());
    }

    public function test_full_auto_suspension_restores_service_after_the_last_delinquent_invoice_is_voided(): void
    {
        config(['skybase_billing.customer_full_auto_suspension' => true]);
        [$tenant, $customer] = $this->createTenantContext('full-suspension');
        $subscription = $this->createSubscription($tenant, $customer, true);
        $invoice = $this->createOverdueInvoice($tenant, $customer, $subscription);
        $billing = app(BillingService::class);

        $this->assertSame(1, $billing->suspendOverdueSubscriptions(today()));
        $this->assertSame('suspended', $subscription->fresh()->status);
        $this->assertSame(0, $billing->suspendOverdueSubscriptions(today()));

        $invoice->update(['status' => 'void', 'balance_due' => 0]);
        $billing->reconcileSubscriptionAfterPayment($subscription, today());

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertFalse($subscription->restrictions()->active()->where('type', 'billing')->exists());
    }

    public function test_billing_reconciliation_preserves_an_operator_suspension(): void
    {
        config(['skybase_billing.customer_full_auto_suspension' => true]);
        [$tenant, $customer] = $this->createTenantContext('operator-suspension');
        $subscription = $this->createSubscription($tenant, $customer, true);
        $invoice = $this->createOverdueInvoice($tenant, $customer, $subscription);
        $operator = User::factory()->create(['tenant_id' => $tenant->id]);
        $billing = app(BillingService::class);

        $this->assertSame(1, $billing->suspendOverdueSubscriptions(today()));
        $subscription->fresh()->suspend(userId: $operator->id);
        $invoice->update(['status' => 'paid', 'paid_amount' => 100, 'balance_due' => 0]);
        $billing->reconcileSubscriptionAfterPayment($subscription, today());

        $this->assertSame('suspended', $subscription->fresh()->status);
        $this->assertFalse($subscription->restrictions()->active()->where('type', 'billing')->exists());
    }

    #[DataProvider('paymentTerms')]
    public function test_recurring_invoice_is_due_through_the_last_day_of_its_payment_term(int $days, string $dueDate): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 12:00:00'));
        [$tenant, $customer] = $this->createTenantContext('payment-term');
        $subscription = $this->createSubscription($tenant, $customer, true);
        $subscription->update(['grace_period_days' => $days]);
        $subscription->items()->create([
            'tenant_id' => $tenant->id,
            'item_type' => 'plan',
            'description' => 'Monthly service',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
            'total' => 100,
            'recurring' => true,
        ]);
        $billing = app(BillingService::class);

        $invoice = $billing->createInvoiceForSubscription($subscription);
        $this->assertSame('2026-11-10', $invoice->issue_date->toDateString());
        $this->assertSame($dueDate, $invoice->due_date->toDateString());
        $this->assertSame('issued', $invoice->status);
        $this->assertSame(0, $billing->markOverdueInvoices($invoice->due_date));
        $this->assertSame(1, $billing->markOverdueInvoices($invoice->due_date->addDay()));
    }

    public static function paymentTerms(): array
    {
        return [
            'seven days' => [7, '2026-11-17'],
            'zero-day setting' => [0, '2026-11-11'],
        ];
    }

    public function test_new_subscription_defaults_to_auto_suspension_enabled(): void
    {
        [$tenant, $customer] = $this->createTenantContext('default-enabled');

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(8)),
            'status' => 'active',
        ]);

        $this->assertTrue($subscription->fresh()->auto_suspension_enabled);
    }

    /**
     * @return array{Tenant, Customer}
     */
    private function createTenantContext(string $slug): array
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => "{$slug}@example.com",
            'timezone' => 'UTC',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'name' => 'Billing Customer',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'status' => 'active',
        ]);

        return [$tenant, $customer];
    }

    private function createSubscription(Tenant $tenant, Customer $customer, bool $autoSuspensionEnabled): Subscription
    {
        return Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-'.Str::upper(Str::random(8)),
            'billing_enabled' => true,
            'auto_suspension_enabled' => $autoSuspensionEnabled,
            'status' => 'active',
        ]);
    }

    private function createOverdueInvoice(
        Tenant $tenant,
        Customer $customer,
        Subscription $subscription,
    ): Invoice {
        return Invoice::withoutEvents(fn (): Invoice => Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'paid_amount' => 0,
            'balance_due' => 100,
            'status' => 'issued',
        ]));
    }
}
