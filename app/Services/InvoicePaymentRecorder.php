<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Validation\ValidationException;

class InvoicePaymentRecorder
{
    public function record(Invoice $invoice, array $validated): Payment
    {
        return $invoice->getConnection()->transaction(function () use ($invoice, $validated): Payment {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $billing = app(BillingService::class);
            $notifications = app(TenantNotificationService::class);
            if (! in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true) || (float) $invoice->balance_due <= 0) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'A payment can only be recorded for an outstanding invoice.',
                ]);
            }

            if ((float) $validated['amount'] > (float) $invoice->balance_due) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount cannot exceed the invoice balance.',
                ]);
            }

            $payment = $invoice->payments()->create([
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $invoice->customer_id,
                'payment_reference' => $validated['payment_reference'] ?? $this->generatePaymentReference($invoice->tenant_id),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'status' => 'completed',
                'paid_at' => $validated['paid_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->increment('paid_amount', $payment->amount);
            $invoice = $invoice->fresh(['customer']);
            $invoice->recalculateTotals();
            if ($invoice->subscription) {
                $billing->reconcileSubscriptionAfterPayment($invoice->subscription);
            }
            $payment = $payment->fresh(['customer', 'invoice']);

            $payment->getConnection()->afterCommit(function () use ($payment, $notifications): void {
                if ($payment->customer) {
                    $notifications->notifyCustomer($payment->customer, NotificationEventRegistry::PAYMENT_RECEIVED, [
                        'title' => 'Payment received',
                        'body' => "Payment {$payment->payment_reference} was recorded.",
                        'category' => 'billing',
                        'severity' => 'success',
                        'action_url' => route('customer.invoices.index'),
                    ], $payment);
                }

                $notifications->notifyAdmins($payment->tenant_id, NotificationEventRegistry::PAYMENT_RECEIVED, [
                    'title' => 'Payment received',
                    'body' => "Payment {$payment->payment_reference} was recorded.",
                    'action_url' => route('billing.payments.show', $payment),
                ], $payment);

            });

            return $payment;
        });
    }

    protected function generatePaymentReference(string $tenantId): string
    {
        do {
            $reference = 'PAY-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (
            Payment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('payment_reference', $reference)
                ->exists()
        );

        return $reference;
    }
}
