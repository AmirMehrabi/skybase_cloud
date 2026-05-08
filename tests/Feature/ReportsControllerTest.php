<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NetworkUsageRecord;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_report_uses_real_tenant_scoped_usage_records(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);

        $plan = Plan::factory()->create(['name' => 'Fiber 500']);
        $router = Router::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Core Alpha']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Acme Networks',
            'email' => 'acme@example.com',
            'status' => 'active',
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-ALPHA-001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'ip_address' => '10.0.0.10',
            'status' => 'active',
            'start_date' => now()->subMonth(),
        ]);

        NetworkUsageRecord::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'router_id' => $router->id,
            'ip_address' => '10.0.0.10',
            'download_bytes' => 1024,
            'upload_bytes' => 512,
            'session_seconds' => 3600,
            'started_at' => now()->subDay(),
            'last_activity_at' => now(),
        ]);

        $otherCustomer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Hidden Customer',
            'status' => 'active',
        ]);
        NetworkUsageRecord::create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $otherCustomer->id,
            'download_bytes' => 999999,
            'upload_bytes' => 999999,
            'last_activity_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.usage'));

        $response->assertOk();
        $response->assertSee('Acme Networks');
        $response->assertDontSee('Hidden Customer');
        $response->assertViewHas('usageReports', function (array $usageReports): bool {
            return $usageReports['summary']['totalUsage'] === 1536
                && $usageReports['summary']['activeUsers'] === 1
                && $usageReports['records']->first()['customer'] === 'Acme Networks'
                && $usageReports['records']->first()['total'] === 1536
                && $usageReports['routerOptions']->first()['label'] === 'Core Alpha';
        });
    }

    public function test_financial_report_uses_real_tenant_scoped_billing_data(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);

        $plan = Plan::factory()->create(['name' => 'Business Fiber']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Revenue Customer',
            'email' => 'revenue@example.com',
            'status' => 'active',
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-REVENUE-001',
            'plan_id' => $plan->id,
            'status' => 'active',
            'start_date' => now()->subMonth(),
        ]);
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-ALPHA-001',
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 100,
            'balance_due' => 0,
            'status' => 'paid',
        ]);
        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-ALPHA-002',
            'billing_period_start' => now()->subMonthNoOverflow()->startOfMonth(),
            'billing_period_end' => now()->subMonthNoOverflow()->endOfMonth(),
            'issue_date' => now()->subMonthNoOverflow()->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'subtotal' => 50,
            'total' => 50,
            'paid_amount' => 0,
            'balance_due' => 50,
            'status' => 'overdue',
        ]);
        Payment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-ALPHA-001',
            'amount' => 100,
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $otherCustomer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Hidden Revenue',
            'status' => 'active',
        ]);
        $otherInvoice = Invoice::create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'INV-HIDDEN-001',
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 900,
            'total' => 900,
            'paid_amount' => 900,
            'balance_due' => 0,
            'status' => 'paid',
        ]);
        Payment::create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $otherCustomer->id,
            'invoice_id' => $otherInvoice->id,
            'payment_reference' => 'PAY-HIDDEN-001',
            'amount' => 900,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.financial'));

        $response->assertOk();
        $response->assertSee('Revenue Customer');
        $response->assertDontSee('Hidden Revenue');
        $response->assertViewHas('financialReports', function (array $financialReports): bool {
            return $financialReports['summary']['revenueThisMonth'] === 100.0
                && $financialReports['summary']['outstandingBalance'] === 50.0
                && $financialReports['summary']['overdueAmount'] === 50.0
                && $financialReports['summary']['arpu'] === 100.0
                && $financialReports['topCustomers']->first()['name'] === 'Revenue Customer'
                && $financialReports['paymentMethods']->first()['amount'] === 100.0;
        });
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'data' => [],
        ]);
    }
}
