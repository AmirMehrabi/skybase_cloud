<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'revenue' => (float) Payment::query()->where('status', 'completed')->sum('amount'),
            'outstanding' => (float) Invoice::query()->outstanding()->sum('balance_due'),
            'overdue' => (float) Invoice::query()->overdue()->sum('balance_due'),
            'paidInvoices' => Invoice::query()->where('status', 'paid')->count(),
            'unpaidInvoices' => Invoice::query()->outstanding()->count(),
            'overdueInvoices' => Invoice::query()->overdue()->count(),
            'pendingInvoices' => Invoice::query()->where('status', 'issued')->count(),
            'customersWithBalance' => Customer::query()->where('balance', '>', 0)->count(),
        ];

        $recentInvoices = Invoice::query()
            ->with(['customer', 'subscription'])
            ->latest()
            ->limit(7)
            ->get()
            ->map(fn (Invoice $invoice): array => $this->transformInvoice($invoice));

        $revenueChart = collect(range(5, 0))->map(function (int $offset): array {
            $monthStart = now()->startOfMonth()->subMonthsNoOverflow($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $revenue = (float) Invoice::query()
                ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('total');

            $collected = (float) Payment::query()
                ->where('status', 'completed')
                ->whereBetween('paid_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])
                ->sum('amount');

            return [
                'month' => $monthStart->format('M'),
                'revenue' => $revenue,
                'collected' => $collected,
            ];
        })->values();

        $chartMax = max(1, (float) $revenueChart->pluck('revenue')->merge($revenueChart->pluck('collected'))->max());

        $billingDashboard = [
            'stats' => $stats,
            'revenueChart' => $revenueChart->map(function (array $month) use ($chartMax): array {
                $month['revenueHeight'] = max(($month['revenue'] / $chartMax) * 100, 8);
                $month['collectedHeight'] = max(($month['collected'] / $chartMax) * 100, 8);

                return $month;
            })->values(),
            'recentInvoices' => $recentInvoices,
        ];

        return view('billing.dashboard', compact('billingDashboard'));
    }

    protected function transformInvoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer?->full_name ?? 'N/A',
            'subscription_code' => $invoice->subscription?->subscription_code ?? 'N/A',
            'total' => (float) $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
            'balance_due' => (float) $invoice->balance_due,
        ];
    }
}
