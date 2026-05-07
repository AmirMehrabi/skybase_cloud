<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_dashboard_exposes_numeric_tenant_scoped_totals(): void
    {
        $tenant = $this->createTenant('alpha-billing');
        $otherTenant = $this->createTenant('beta-billing');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'balance' => 45,
        ]);

        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'balance' => 900,
        ]);

        $paidInvoice = $this->createInvoice($tenant, $customer, [
            'invoice_number' => 'INV-ALPHA-PAID',
            'total' => 100,
            'paid_amount' => 100,
            'balance_due' => 0,
            'status' => 'paid',
        ]);

        $this->createInvoice($tenant, $customer, [
            'invoice_number' => 'INV-ALPHA-ISSUED',
            'total' => 250.5,
            'paid_amount' => 25.25,
            'balance_due' => 225.25,
            'status' => 'issued',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->createInvoice($tenant, $customer, [
            'invoice_number' => 'INV-ALPHA-OVERDUE',
            'total' => 300,
            'paid_amount' => 50,
            'balance_due' => 250,
            'status' => 'overdue',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $otherPaidInvoice = $this->createInvoice($otherTenant, $otherCustomer, [
            'invoice_number' => 'INV-BETA-PAID',
            'total' => 999,
            'paid_amount' => 999,
            'balance_due' => 0,
            'status' => 'paid',
        ]);

        $this->createPayment($tenant, $customer, $paidInvoice, [
            'payment_reference' => 'PAY-ALPHA-COMPLETED',
            'amount' => 75.25,
            'status' => 'completed',
        ]);

        $this->createPayment($otherTenant, $otherCustomer, $otherPaidInvoice, [
            'payment_reference' => 'PAY-BETA-COMPLETED',
            'amount' => 999,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get(route('billing.dashboard'));

        $response->assertOk();
        $response->assertViewHas('billingDashboard', function (array $billingDashboard): bool {
            return $billingDashboard['stats']['revenue'] === 75.25
                && $billingDashboard['stats']['outstanding'] === 475.25
                && $billingDashboard['stats']['overdue'] === 250.0
                && $billingDashboard['stats']['paidInvoices'] === 1
                && $billingDashboard['stats']['unpaidInvoices'] === 2
                && $billingDashboard['stats']['overdueInvoices'] === 1
                && $billingDashboard['stats']['pendingInvoices'] === 1
                && $billingDashboard['stats']['customersWithBalance'] === 1;
        });
        $response->assertSee('"revenue":75.25', false);
        $response->assertDontSee('$NaN', false);
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => Str::headline($slug),
            'slug' => $slug,
            'company_name' => Str::headline($slug),
            'email' => "{$slug}@example.com",
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createInvoice(Tenant $tenant, Customer $customer, array $overrides = []): Invoice
    {
        return Invoice::withoutEvents(fn (): Invoice => Invoice::create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => null,
            'invoice_number' => 'INV-'.Str::upper(Str::random(8)),
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'balance_due' => 0,
            'status' => 'issued',
        ], $overrides)));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPayment(Tenant $tenant, Customer $customer, Invoice $invoice, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-'.Str::upper(Str::random(8)),
            'amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
        ], $overrides));
    }
}
