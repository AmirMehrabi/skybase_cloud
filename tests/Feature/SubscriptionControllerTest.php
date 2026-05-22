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

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_uses_the_subscription_status_from_the_model(): void
    {
        $tenant = $this->createTenant('alpha-net', 'AlphaNet Communications');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'customer_code' => 'CUS-TEST-0001',
            'customer_type' => 'individual',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'mobile' => '555-0101',
            'address_line1' => '123 Main Street',
            'city' => 'Springfield',
            'country' => 'United States',
            'status' => 'active',
            'billing_type' => 'postpaid',
            'balance' => 0,
            'credit_limit' => 100,
            'tax_exempt' => false,
        ]);

        $plan = Plan::factory()->create([
            'status' => 'active',
            'name' => 'Fiber 100',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
        ]);

        $router = Router::factory()->online()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Core Router',
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_code' => 'SUB-TEST-0001',
            'plan_id' => $plan->id,
            'router_id' => $router->id,
            'site' => 'Downtown Office',
            'connection_type' => 'pppoe',
            'ip_address' => '192.168.1.100',
            'pppoe_username' => 'jane.doe',
            'pppoe_password' => 'secret',
            'base_price' => 79.99,
            'discount_amount' => 0,
            'discount_type' => 'none',
            'tax_amount' => 0,
            'total_price' => 79.99,
            'billing_cycle' => 'monthly',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-TEST-0001',
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->subDays(7)->toDateString(),
            'due_date' => now()->addDays(23)->toDateString(),
            'subtotal' => 79.99,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 79.99,
            'paid_amount' => 54.99,
            'balance_due' => 25.00,
            'status' => 'partially_paid',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-TEST-0001',
            'amount' => 54.99,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now()->subDays(2),
        ]);

        NetworkUsageRecord::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'router_id' => $router->id,
            'ip_address' => '192.168.1.100',
            'download_bytes' => 2147483648,
            'upload_bytes' => 1073741824,
            'session_seconds' => 5400,
            'started_at' => now()->subHours(6),
            'ended_at' => now()->subHours(4),
            'last_activity_at' => now()->subHours(4),
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.show', $subscription));

        $response->assertOk();
        $response->assertSee('Pending');
        $response->assertDontSee('Suspend');
        $response->assertSee('Activate');
        $response->assertSee('INV-TEST-0001');
        $response->assertSee('3.00 GB');
        $response->assertViewHas('subscription', function (mixed $viewSubscription): bool {
            return $viewSubscription instanceof Subscription
                && $viewSubscription->status === 'pending'
                && $viewSubscription->subscription_code === 'SUB-TEST-0001';
        });
        $response->assertViewHas('billingInvoices', function (mixed $billingInvoices): bool {
            return is_array($billingInvoices)
                && count($billingInvoices) === 1
                && $billingInvoices[0]['invoice_number'] === 'INV-TEST-0001'
                && (float) $billingInvoices[0]['balance_due'] === 25.0;
        });
        $response->assertViewHas('usageSummary', function (mixed $usageSummary): bool {
            return is_array($usageSummary)
                && (float) $usageSummary['total_gb'] === 3.0
                && (int) $usageSummary['sessions'] === 1;
        });
        $response->assertViewHas('usageSessions', function (mixed $usageSessions): bool {
            return is_array($usageSessions)
                && count($usageSessions) === 1
                && $usageSessions[0]['download'] === '2.00 GB'
                && $usageSessions[0]['upload'] === '1.00 GB';
        });
    }

    private function createTenant(string $slug, string $companyName): Tenant
    {
        return Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $companyName,
            'slug' => $slug,
            'company_name' => $companyName,
            'email' => $slug.'@example.com',
            'timezone' => 'UTC',
            'status' => 'active',
        ]);
    }
}
