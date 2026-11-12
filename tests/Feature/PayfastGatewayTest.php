<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\GatewayPaymentAttempt;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PayfastGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PayfastGatewayTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create(['id' => (string) Str::uuid(), 'name' => 'Test ISP', 'company_name' => 'Test ISP', 'slug' => Str::random(10), 'email' => 'isp@example.com', 'status' => 'active', 'currency' => 'ZAR']);
    }

    public function test_settings_follow_existing_navigation_and_keep_secrets_out_of_html(): void
    {
        $tenant = $this->tenant();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->actingAs($admin)->put(route('settings.update.payment-gateway'), ['enabled' => 1, 'environment' => 'sandbox', 'label' => 'Pay now', 'sandbox' => ['merchant_id' => '10000100', 'merchant_key' => 'secret-key', 'passphrase' => 'secret-pass']])->assertRedirect()->assertSessionHasNoErrors();
        $setting = Setting::query()->where('tenant_id', $tenant->id)->where('key', 'payment.payfast')->firstOrFail();
        $this->assertStringNotContainsString('secret-key', $setting->getRawOriginal('value'));
        $this->get(route('settings.section', ['section' => 'payment-gateway']))->assertOk()->assertSee('Save payment gateway')->assertDontSee('secret-key')->assertDontSee('secret-pass');
        $this->put(route('settings.update.payment-gateway'), ['enabled' => 1, 'environment' => 'sandbox', 'label' => 'Pay now'])->assertSessionHasNoErrors();
        $this->assertSame('secret-key', app(PayfastGateway::class)->settings($tenant->id)['sandbox']['merchant_key']);
    }

    public function test_exact_amount_conversion_and_signature(): void
    {
        $gateway = new PayfastGateway;
        $this->assertSame(10001, $gateway->minor('100.01'));
        $this->assertSame(md5('amount=10.00&item_name=Test+invoice&passphrase=a+b'), $gateway->signature(['amount' => '10.00', 'item_name' => 'Test invoice'], 'a b'));
    }

    public function test_invalid_callback_token_is_rejected(): void
    {
        $this->post(route('payfast.notify', ['token' => 'invalid']))->assertStatus(400);
    }

    private function attempt(string $environment): GatewayPaymentAttempt
    {
        $tenant = $this->tenant();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $invoice = Invoice::query()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'invoice_number' => 'INV-1', 'billing_period_start' => today(), 'billing_period_end' => today()->addMonth(), 'issue_date' => today(), 'due_date' => today()->addDay(), 'total' => 100, 'paid_amount' => 0, 'balance_due' => 100, 'status' => 'issued']);
        $invoice->items()->create(['tenant_id' => $tenant->id, 'description' => 'Internet', 'quantity' => 1, 'unit_price' => 100, 'subtotal' => 100, 'tax_amount' => 0, 'total' => 100]);

        return GatewayPaymentAttempt::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'environment' => $environment]);
    }

    private function sendNotification(GatewayPaymentAttempt $attempt, array $overrides = []): TestResponse
    {
        Cache::put('payfast.source-addresses', ['127.0.0.1'], 300);
        $data = array_replace(['merchant_id' => '10000100', 'm_payment_id' => $attempt->reference, 'pf_payment_id' => '123456', 'payment_status' => 'COMPLETE', 'amount_gross' => '100.00'], $overrides);
        $data['signature'] = app(PayfastGateway::class)->signature($data, 'test-pass');

        return $this->post(route('payfast.notify', ['token' => Crypt::encryptString($attempt->tenant_id.'|'.$attempt->reference)]), $data);
    }

    public function test_verified_live_callback_settles_invoice_exactly_once(): void
    {
        Http::fake(['*' => Http::response('VALID')]);
        $attempt = $this->attempt('live');
        $this->sendNotification($attempt)->assertOk();
        $this->sendNotification($attempt)->assertOk();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('invoices', ['id' => $attempt->invoice_id, 'status' => 'paid', 'balance_due' => 0]);
    }

    public function test_sandbox_callback_does_not_settle_invoice(): void
    {
        Http::fake(['*' => Http::response('VALID')]);
        $attempt = $this->attempt('sandbox');
        $this->sendNotification($attempt)->assertOk();
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('gateway_payment_attempts', ['id' => $attempt->id, 'status' => 'sandbox_completed']);
    }

    public function test_amount_mismatch_and_failed_server_validation_cannot_record_payments(): void
    {
        Http::fake(['*' => Http::response('INVALID')]);
        $attempt = $this->attempt('live');
        $this->sendNotification($attempt, ['amount_gross' => '1.00'])->assertStatus(400);
        $this->sendNotification($attempt)->assertStatus(400);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_for_cancelled_invoice_is_retained_for_review(): void
    {
        Http::fake(['*' => Http::response('VALID')]);
        $attempt = $this->attempt('live');
        Invoice::query()->where('tenant_id', $attempt->tenant_id)->whereKey($attempt->invoice_id)->update(['status' => 'cancelled']);
        $this->sendNotification($attempt)->assertOk();
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('gateway_payment_attempts', ['id' => $attempt->id, 'status' => 'review']);
    }

    public function test_customer_checkout_uses_saved_methods_and_reuses_pending_attempt(): void
    {
        $attempt = $this->attempt('sandbox');
        $tenant = Tenant::query()->findOrFail($attempt->tenant_id);
        $customer = Customer::query()->where('tenant_id', $tenant->id)->whereKey($attempt->customer_id)->firstOrFail();
        $customer->update(['status' => 'active']);
        Setting::set('payment.payfast', ['enabled' => true, 'environment' => 'sandbox', 'label' => 'Pay now', 'methods' => ['cc'], 'sandbox' => $attempt->credentials], 'json', $tenant->id);
        $this->actingAs($customer, 'customer')->get(route('customer.invoices.index'))->assertOk()->assertSee('Pay now');
        $this->post(route('customer.invoices.pay', $attempt->invoice_id), ['method' => 'ef'])->assertStatus(422);
        $this->post(route('customer.invoices.pay', $attempt->invoice_id), ['method' => 'cc'])->assertOk()->assertSee('Continue to PayFast');
        $this->post(route('customer.invoices.pay', $attempt->invoice_id), ['method' => 'cc'])->assertOk();
        $this->assertDatabaseCount('gateway_payment_attempts', 2);
        $this->get(route('customer.payments.status', $attempt->reference))->assertOk()->assertSee('Awaiting payment confirmation');
        $other = Customer::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        $this->actingAs($other, 'customer')->post(route('customer.invoices.pay', $attempt->invoice_id), ['method' => 'cc'])->assertNotFound();
        $this->get(route('customer.payments.status', $attempt->reference))->assertNotFound();
    }

    public function test_foreign_tenant_callback_reference_cannot_access_attempt(): void
    {
        $attempt = $this->attempt('live');
        $otherTenant = $this->tenant();
        Cache::put('payfast.source-addresses', ['127.0.0.1'], 300);
        $this->post(route('payfast.notify', ['token' => Crypt::encryptString($otherTenant->id.'|'.$attempt->reference)]))->assertNotFound();
    }

    public function test_signature_tampering_and_untrusted_source_are_rejected(): void
    {
        $attempt = $this->attempt('live');
        Cache::put('payfast.source-addresses', ['127.0.0.1'], 300);
        $this->post(route('payfast.notify', ['token' => Crypt::encryptString($attempt->tenant_id.'|'.$attempt->reference)]), ['m_payment_id' => $attempt->reference, 'merchant_id' => '10000100', 'amount_gross' => '100.00', 'signature' => 'bad'])->assertStatus(400);
        Http::assertNothingSent();
        Cache::put('payfast.source-addresses', ['192.0.2.1'], 300);
        $this->post(route('payfast.notify', ['token' => Crypt::encryptString($attempt->tenant_id.'|'.$attempt->reference)]))->assertStatus(400);
    }
}
