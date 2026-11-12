<?php

namespace App\Http\Controllers;

use App\Http\Requests\Billing\CheckoutInvoiceRequest;
use App\Http\Requests\Setting\UpdatePaymentGatewayRequest;
use App\Models\GatewayPaymentAttempt;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\InvoicePaymentRecorder;
use App\Services\PayfastGateway;
use App\Services\TenantContext;
use App\Support\CustomerPortalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function update(UpdatePaymentGatewayRequest $request, PayfastGateway $gateway): RedirectResponse
    {
        $settings = array_replace_recursive($gateway->settings(tenant()->id), $request->validated());
        $settings['enabled'] = $request->boolean('enabled');
        $settings['methods'] = $request->input('methods', []);
        foreach (['sandbox', 'live'] as $environment) {
            foreach (['merchant_key', 'passphrase'] as $key) {
                if (! $request->filled($environment.'.'.$key)) {
                    $settings[$environment][$key] = $gateway->settings(tenant()->id)[$environment][$key] ?? null;
                }
            }
        }
        if ($settings['enabled'] && ! $gateway->ready($settings)) {
            throw ValidationException::withMessages(['enabled' => 'Enter the merchant ID, merchant key and passphrase for the selected mode.']);
        }
        Setting::query()->updateOrCreate(['tenant_id' => tenant()->id, 'key' => 'payment.payfast'], ['value' => $settings, 'type' => 'json', 'group' => 'billing']);

        return back()->with('success', 'Payment gateway settings saved.');
    }

    public function checkout(CheckoutInvoiceRequest $request, int $invoice, PayfastGateway $gateway): View
    {
        $customer = $request->user('customer');
        abort_unless(CustomerPortalSettings::showsBilling($customer->tenant_id), 404);
        $invoice = $customer->invoices()->whereKey($invoice)->firstOrFail();
        $settings = $gateway->settings($customer->tenant_id);
        abort_unless($gateway->ready($settings), 422, 'Payment gateway is unavailable.');
        abort_unless(tenant()->currency === 'ZAR', 422, 'PayFast requires invoices billed in ZAR.');
        abort_unless(in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true) && $gateway->minor($invoice->balance_due) >= 500, 422, 'This invoice is not eligible for PayFast payment (minimum R5).');
        $method = $request->input('method');
        abort_unless(is_null($method) || is_string($method), 422);
        abort_unless($settings['methods'] === [] ? $method === null : in_array($method, $settings['methods'], true), 422, 'Choose an enabled payment method.');
        $attempt = $invoice->getConnection()->transaction(function () use ($invoice, $customer, $settings, $method, $gateway): GatewayPaymentAttempt {
            $invoice = $customer->invoices()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true) && $gateway->minor($invoice->balance_due) >= 500, 422);
            $pending = GatewayPaymentAttempt::query()->where('invoice_id', $invoice->id)->where('status', 'pending')->where('environment', $settings['environment'])->where('amount_minor', $gateway->minor($invoice->balance_due))->where('method', $method)->where('created_at', '>=', now()->subMinutes(15))->latest('id')->first();
            if ($pending && $pending->credentials === $settings[$settings['environment']]) {
                return $pending;
            }

            return GatewayPaymentAttempt::query()->create(['tenant_id' => $customer->tenant_id, 'customer_id' => $customer->id, 'invoice_id' => $invoice->id, 'reference' => (string) Str::uuid(), 'environment' => $settings['environment'], 'amount_minor' => $gateway->minor($invoice->balance_due), 'method' => $method, 'credentials' => $settings[$settings['environment']]]);
        });
        $credentials = $attempt->credentials;
        $return = route('customer.payments.status', $attempt->reference);
        $data = ['merchant_id' => $credentials['merchant_id'], 'merchant_key' => $credentials['merchant_key'], 'return_url' => $return, 'cancel_url' => $return.'?cancelled=1', 'notify_url' => route('payfast.notify', ['token' => Crypt::encryptString($customer->tenant_id.'|'.$attempt->reference)]), 'm_payment_id' => $attempt->reference, 'amount' => number_format($attempt->amount_minor / 100, 2, '.', ''), 'item_name' => 'Invoice '.$invoice->invoice_number];
        if ($method && $attempt->environment === 'live') {
            $data['payment_method'] = $method;
        }
        $data['signature'] = $gateway->signature($data, $credentials['passphrase']);

        return view('modules.payment-gateway.checkout', ['data' => $data, 'endpoint' => $gateway->host($attempt->environment).'/eng/process', 'attempt' => $attempt]);
    }

    public function status(Request $request, string $reference): View
    {
        abort_unless(CustomerPortalSettings::showsBilling($request->user('customer')->tenant_id), 404);
        $attempt = GatewayPaymentAttempt::query()->where('customer_id', $request->user('customer')->id)->where('reference', $reference)->firstOrFail();

        return view('modules.payment-gateway.status', compact('attempt'));
    }

    public function notify(Request $request, string $token, PayfastGateway $gateway, TenantContext $context): Response
    {
        try {
            [$tenantId, $reference] = explode('|', Crypt::decryptString($token), 2);
        } catch (\Throwable) {
            abort(400);
        }

        return $context->run($tenantId, function () use ($request, $reference, $gateway): Response {
            abort_unless($gateway->validSource((string) $request->ip()), 400);
            $attempt = GatewayPaymentAttempt::query()->where('reference', $reference)->firstOrFail();
            $data = $request->post();
            abort_unless(($data['m_payment_id'] ?? '') === $reference && is_scalar($data['amount_gross'] ?? null) && preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', (string) $data['amount_gross']), 400);
            foreach ($data as $value) {
                abort_unless(is_scalar($value), 400);
            }
            abort_unless($gateway->minor((string) $data['amount_gross']) === $attempt->amount_minor && $gateway->validate($data, $attempt->credentials, $attempt->environment), 400);
            abort_unless(filled($data['pf_payment_id'] ?? null), 400);
            if (($data['payment_status'] ?? '') !== 'COMPLETE') {
                return response('OK');
            }
            $attempt->getConnection()->transaction(function () use ($attempt, $data): void {
                $locked = GatewayPaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
                if (in_array($locked->status, ['completed', 'sandbox_completed', 'review'], true)) {
                    return;
                }
                $locked->provider_reference = (string) $data['pf_payment_id'];
                if ($locked->environment === 'sandbox') {
                    $locked->status = 'sandbox_completed';
                    $locked->save();

                    return;
                }
                $invoice = Invoice::query()->whereKey($locked->invoice_id)->where('customer_id', $locked->customer_id)->lockForUpdate()->firstOrFail();
                if (! in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true) || app(PayfastGateway::class)->minor($invoice->balance_due) < $locked->amount_minor) {
                    $locked->status = 'review';
                    $locked->save();

                    return;
                }
                $amount = number_format($locked->amount_minor / 100, 2, '.', '');
                app(InvoicePaymentRecorder::class)->record($invoice, ['amount' => $amount, 'payment_reference' => 'PF-'.$locked->provider_reference, 'payment_method' => 'payfast', 'notes' => 'PayFast '.$locked->reference]);
                $locked->status = 'completed';
                $locked->save();
            });

            return response('OK');
        });
    }
}
