<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingInvoiceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_can_be_recorded_from_an_invoice_show_page_without_an_invoice_id_payload(): void
    {
        [$tenant, $user, $customer] = $this->createTenantContext('payment');
        $invoice = $this->createInvoice($tenant, $customer);

        $response = $this->actingAs($user)->postJson(route('billing.invoices.payments.store', $invoice), [
            'amount' => 40,
            'payment_method' => 'cash',
            'paid_at' => now()->toDateString(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('invoice.paid_amount', '40.00')
            ->assertJsonPath('invoice.balance_due', '60.00')
            ->assertJsonPath('invoice.status', 'partially_paid');

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 40,
            'status' => 'completed',
        ]);
    }

    public function test_payments_page_only_provides_outstanding_invoices_with_their_customer(): void
    {
        [$tenant, $user, $customer] = $this->createTenantContext('payment-invoices');
        $outstandingInvoice = $this->createInvoice($tenant, $customer);
        $paidInvoice = $this->createInvoice($tenant, $customer, [
            'paid_amount' => 100,
            'balance_due' => 0,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('billing.payments.index'));

        $response
            ->assertOk()
            ->assertViewHas('invoices', function ($invoices) use ($outstandingInvoice, $paidInvoice, $customer): bool {
                return $invoices->count() === 1
                    && $invoices->first()['id'] === $outstandingInvoice->id
                    && $invoices->first()['customer_id'] === $customer->id
                    && ! $invoices->contains(fn (array $invoice): bool => $invoice['id'] === $paidInvoice->id);
            });
    }

    public function test_payment_cannot_be_recorded_against_a_different_selected_customer(): void
    {
        [$tenant, $user, $customer] = $this->createTenantContext('payment-customer');
        $anotherCustomer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-'.Str::upper(Str::random(8)),
            'customer_type' => 'individual',
            'name' => 'Another Customer',
            'billing_type' => 'postpaid',
            'billing_enabled' => true,
            'balance' => 0,
            'credit_limit' => 0,
            'tax_exempt' => false,
            'status' => 'active',
        ]);
        $invoice = $this->createInvoice($tenant, $customer);

        $this->actingAs($user)
            ->postJson(route('billing.payments.store'), [
                'customer_id' => $anotherCustomer->id,
                'invoice_id' => $invoice->id,
                'amount' => 40,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['invoice_id']);
    }

    public function test_credit_note_is_persisted_for_the_selected_tenant_customer(): void
    {
        [$tenant, $user, $customer] = $this->createTenantContext('credit-note');

        $this->actingAs($user)
            ->postJson(route('billing.credits.store'), [
                'customer_id' => $customer->id,
                'amount' => 25,
                'reason' => 'adjustment',
            ])
            ->assertCreated()
            ->assertJsonPath('credit.total_credits', 25);

        $this->assertDatabaseHas('customer_credits', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'amount' => 25,
            'status' => 'applied',
        ]);
        $this->assertSame(-25.0, (float) $customer->fresh()->balance);
    }

    public function test_invoice_without_payments_can_be_cancelled(): void
    {
        [$tenant, $user, $customer] = $this->createTenantContext('cancel');
        $invoice = $this->createInvoice($tenant, $customer);

        $response = $this->actingAs($user)->patchJson(route('billing.invoices.cancel', $invoice));

        $response
            ->assertOk()
            ->assertJsonPath('invoice.status', 'void');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'status' => 'void',
        ]);
    }

    public function test_invoice_with_recorded_payments_cannot_be_cancelled(): void
    {
        [$tenant, $user, $customer] = $this->createTenantContext('paid-cancel');
        $invoice = $this->createInvoice($tenant, $customer, [
            'paid_amount' => 25,
            'balance_due' => 75,
            'status' => 'partially_paid',
        ]);

        $response = $this->actingAs($user)->patchJson(route('billing.invoices.cancel', $invoice));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['invoice']);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'partially_paid',
        ]);
    }

    /**
     * @return array{Tenant, User, Customer}
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

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
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

        return [$tenant, $user, $customer];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createInvoice(Tenant $tenant, Customer $customer, array $overrides = []): Invoice
    {
        $invoice = Invoice::withoutEvents(fn (): Invoice => Invoice::create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 100,
            'paid_amount' => 0,
            'balance_due' => 100,
            'status' => 'issued',
        ], $overrides)));

        $invoice->items()->create([
            'description' => 'Internet service',
            'quantity' => 1,
            'unit_price' => 100,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'subtotal' => 100,
            'total' => 100,
        ]);

        return $invoice;
    }
}
