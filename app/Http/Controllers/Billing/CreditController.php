<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCredit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'customer_code', 'balance', 'email'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'customer_name' => $customer->full_name,
                'customer_code' => $customer->customer_code,
                'balance' => (float) $customer->balance,
            ]);

        $credits = CustomerCredit::query()
            ->with('customer')
            ->latest()
            ->get()
            ->map(fn (CustomerCredit $credit): array => $this->transformCredit($credit));

        $stats = [
            'totalCredits' => (float) CustomerCredit::query()->sum('amount'),
            'usedCredits' => (float) CustomerCredit::query()->where('status', 'applied')->sum('amount'),
            'availableCredits' => (float) Customer::query()->where('balance', '<', 0)->sum('balance') * -1,
            'customerCount' => Customer::query()->where('balance', '<', 0)->count(),
        ];

        return view('billing.credits', compact('credits', 'customers', 'stats'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $customer = Customer::query()->findOrFail($validated['customer_id']);
        $balanceBefore = (float) $customer->balance;
        $balanceAfter = $balanceBefore - (float) $validated['amount'];

        $credit = CustomerCredit::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'credit_reference' => $this->generateCreditReference($customer->tenant_id),
            'amount' => $validated['amount'],
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reason' => $validated['reason'] ?? 'adjustment',
            'notes' => $validated['notes'] ?? null,
            'issued_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'status' => 'applied',
        ]);

        $customer->update(['balance' => $balanceAfter]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Credit added successfully.',
                'credit' => $this->transformCredit($credit->fresh(['customer'])),
            ], 201);
        }

        return back()->with('success', 'Credit added successfully.');
    }

    protected function transformCredit(CustomerCredit $credit): array
    {
        return [
            'id' => $credit->id,
            'customer_name' => $credit->customer?->full_name ?? 'N/A',
            'customer_code' => $credit->customer?->customer_code ?? 'N/A',
            'balance' => (float) $credit->balance_after,
            'total_credits' => (float) $credit->amount,
            'used' => (float) $credit->amount,
            'last_updated' => $credit->updated_at?->format('M d, Y'),
            'expiry_date' => $credit->expires_at?->toDateString(),
        ];
    }

    protected function generateCreditReference(string $tenantId): string
    {
        do {
            $reference = 'CRD-'.now()->format('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (
            CustomerCredit::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('credit_reference', $reference)
                ->exists()
        );

        return $reference;
    }
}
