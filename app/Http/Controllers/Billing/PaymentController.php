<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

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
        $invoice->fresh()->recalculateTotals();

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'payment' => $payment,
            'invoice' => $invoice->fresh(),
        ], 201);
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
