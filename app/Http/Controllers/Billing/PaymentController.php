<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\TenantNotificationService;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['customer', 'invoice'])
            ->latest()
            ->get()
            ->map(fn (Payment $payment): array => $this->transformPayment($payment));

        $customers = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'customer_code', 'balance'])
            ->map(fn ($customer): array => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'customer_code' => $customer->customer_code,
                'balance' => (float) $customer->balance,
            ]);

        $invoices = Invoice::query()
            ->with('customer')
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer?->full_name ?? 'N/A',
                'balance_due' => (float) $invoice->balance_due,
                'status' => $invoice->status,
            ]);

        $stats = [
            'totalCollected' => (float) Payment::query()->where('status', 'completed')->sum('amount'),
            'pending' => (float) Payment::query()->where('status', 'pending')->sum('amount'),
            'pendingCount' => Payment::query()->where('status', 'pending')->count(),
            'failed' => (float) Payment::query()->where('status', 'failed')->sum('amount'),
            'failedCount' => Payment::query()->where('status', 'failed')->count(),
            'totalCount' => Payment::query()->count(),
        ];

        return view('billing.payments.index', compact('payments', 'stats', 'customers', 'invoices'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['customer', 'invoice']);

        return view('billing.payments.show', [
            'payment' => $this->transformPayment($payment),
        ]);
    }

    public function store(StorePaymentRequest $request, TenantNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $invoice = Invoice::query()->findOrFail($validated['invoice_id']);

        if ($invoice->status === 'void') {
            throw ValidationException::withMessages([
                'invoice_id' => 'A payment cannot be recorded for a cancelled invoice.',
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
            'payment_reference' => $this->generatePaymentReference($invoice->tenant_id),
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'status' => 'completed',
            'paid_at' => $validated['paid_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $invoice->increment('paid_amount', $payment->amount);
        $invoice = $invoice->fresh(['customer']);
        $invoice->recalculateTotals();
        $payment = $payment->fresh(['customer', 'invoice']);

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

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'payment' => $this->transformPayment($payment),
            'invoice' => Invoice::query()->find($invoice->id),
        ], 201);
    }

    protected function transformPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_reference' => $payment->payment_reference,
            'customer_name' => $payment->customer?->full_name ?? 'N/A',
            'customer_id' => $payment->customer?->id,
            'invoice_number' => $payment->invoice?->invoice_number ?? 'N/A',
            'invoice_id' => $payment->invoice_id,
            'invoice_description' => $payment->invoice?->billing_period_start?->format('M j, Y').' - '.$payment->invoice?->billing_period_end?->format('M j, Y'),
            'invoice_date' => $payment->invoice?->issue_date?->toDateString(),
            'invoice_total' => (float) ($payment->invoice?->total ?? 0),
            'amount' => (float) $payment->amount,
            'method' => $payment->payment_method ?? 'cash',
            'date' => $payment->paid_at?->toDateString(),
            'status' => $payment->status,
            'remaining_balance' => (float) ($payment->invoice?->balance_due ?? 0),
        ];
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
