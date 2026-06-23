<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\ActivityLogFormatter;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::query()
            ->with(['customer', 'subscription'])
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice): array => $this->transformInvoice($invoice));

        return view('billing.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'subscription', 'items', 'payments']);

        return view('billing.invoices.show', [
            'invoice' => $this->transformInvoice($invoice, true),
        ]);
    }

    public function generateRecurring(BillingService $billing): RedirectResponse
    {
        $created = $billing->generateDueInvoices();

        return back()->with('success', "Generated {$created} due invoice(s).");
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        if ((float) $invoice->paid_amount > 0) {
            throw ValidationException::withMessages([
                'invoice' => 'An invoice with recorded payments cannot be cancelled.',
            ]);
        }

        $invoice->update(['status' => 'void']);

        return response()->json([
            'message' => 'Invoice cancelled successfully.',
            'invoice' => $this->transformInvoice($invoice->fresh()),
        ]);
    }

    protected function transformInvoice(Invoice $invoice, bool $includeDetails = false): array
    {
        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer?->full_name ?? 'N/A',
            'subscription_code' => $invoice->subscription?->subscription_code ?? 'N/A',
            'issue_date' => $invoice->issue_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'billing_period' => $invoice->billing_period_start?->format('M j, Y').' - '.$invoice->billing_period_end?->format('M j, Y'),
            'subtotal' => (float) $invoice->subtotal,
            'tax' => (float) $invoice->tax_total,
            'discount' => (float) $invoice->discount_total,
            'total' => (float) $invoice->total,
            'paid_amount' => (float) $invoice->paid_amount,
            'balance_due' => (float) $invoice->balance_due,
            'status' => $invoice->status,
            'days_overdue' => $invoice->due_date && $invoice->due_date->isPast() && $invoice->balance_due > 0
                ? $invoice->due_date->diffInDays(today())
                : 0,
            'notes' => $invoice->notes,
        ];

        if ($includeDetails) {
            $data['items'] = $invoice->items->map(fn ($item): array => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])->values();

            $data['payments'] = $invoice->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'date' => $payment->paid_at?->toDateString(),
                'method' => $payment->payment_method ?? 'cash',
                'amount' => (float) $payment->amount,
                'status' => $payment->status,
            ])->values();

            $data['activities'] = app(ActivityLogFormatter::class)
                ->forSubject($invoice, $invoice->tenant_id)
                ->values();
        }

        return $data;
    }
}
