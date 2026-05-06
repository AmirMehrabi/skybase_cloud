<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period', 'this_month')->toString();
        $range = $this->resolveRange($request);

        $invoiceQuery = Invoice::query()->whereBetween('issue_date', [$range['from']->toDateString(), $range['to']->toDateString()]);
        $paymentQuery = Payment::query()->whereBetween('paid_at', [$range['from']->startOfDay(), $range['to']->endOfDay()]);

        $revenue = [
            'total' => (float) $invoiceQuery->sum('total'),
            'collected' => (float) (clone $paymentQuery)->where('status', 'completed')->sum('amount'),
            'outstanding' => (float) Invoice::query()->outstanding()->sum('balance_due'),
            'collectionRate' => 0,
            'overdueInvoices' => Invoice::query()->overdue()->count(),
            'avgCollectionDays' => $this->averageCollectionDays(clone $paymentQuery),
        ];

        $revenue['collectionRate'] = $revenue['total'] > 0 ? (int) round(($revenue['collected'] / $revenue['total']) * 100) : 0;

        $billingReports = [
            'period' => $period,
            'revenue' => $revenue,
            'revenueChart' => $this->revenueChart($range['from']),
            'paymentMethods' => $this->paymentMethods(clone $paymentQuery),
            'topCustomers' => $this->topCustomers($range['from'], $range['to']),
            'agingReport' => $this->agingReport(),
        ];

        return view('billing.reports', compact('billingReports'));
    }

    protected function resolveRange(Request $request): array
    {
        $period = $request->string('period', 'this_month')->toString();
        $from = $request->date('from');
        $to = $request->date('to');

        return match ($period) {
            'last_month' => ['from' => now()->subMonthNoOverflow()->startOfMonth(), 'to' => now()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => ['from' => now()->startOfQuarter(), 'to' => now()->endOfQuarter()],
            'this_year' => ['from' => now()->startOfYear(), 'to' => now()->endOfYear()],
            'custom' => ['from' => $from ?? now()->startOfMonth(), 'to' => $to ?? now()],
            default => ['from' => now()->startOfMonth(), 'to' => now()->endOfMonth()],
        };
    }

    protected function revenueChart(Carbon $anchor): array
    {
        return collect(range(5, 0))->map(function (int $offset) use ($anchor): array {
            $monthStart = $anchor->copy()->startOfMonth()->subMonthsNoOverflow($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();

            return [
                'month' => $monthStart->format('M'),
                'revenue' => (float) Invoice::query()->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->sum('total'),
                'collected' => (float) Payment::query()->where('status', 'completed')->whereBetween('paid_at', [$monthStart->startOfDay(), $monthEnd->endOfDay()])->sum('amount'),
            ];
        })->values()->all();
    }

    protected function paymentMethods($paymentQuery): array
    {
        $methodGroups = $paymentQuery
            ->where('status', 'completed')
            ->selectRaw('payment_method, count(*) as total_count, sum(amount) as total_amount')
            ->groupBy('payment_method')
            ->get()
            ->keyBy(fn ($row) => $row->payment_method ?: 'cash');

        $total = max(1, (float) $methodGroups->sum('total_amount'));

        $labels = [
            'cash' => ['label' => 'Cash', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>', 'bgColor' => 'bg-green-100', 'barColor' => 'bg-green-600'],
            'card' => ['label' => 'Card', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>', 'bgColor' => 'bg-blue-100', 'barColor' => 'bg-blue-600'],
            'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>', 'bgColor' => 'bg-purple-100', 'barColor' => 'bg-purple-600'],
            'check' => ['label' => 'Check', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 'bgColor' => 'bg-orange-100', 'barColor' => 'bg-orange-600'],
            'online' => ['label' => 'Online', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>', 'bgColor' => 'bg-cyan-100', 'barColor' => 'bg-cyan-600'],
        ];

        return collect($labels)->map(function (array $config, string $method) use ($methodGroups, $total): array {
            $row = $methodGroups->get($method);
            $amount = (float) ($row->total_amount ?? 0);

            return [
                'name' => $config['label'],
                'count' => (int) ($row->total_count ?? 0),
                'amount' => $amount,
                'percentage' => (int) round(($amount / $total) * 100),
                'icon' => $config['icon'],
                'bgColor' => $config['bgColor'],
                'barColor' => $config['barColor'],
            ];
        })->values()->all();
    }

    protected function topCustomers(Carbon $from, Carbon $to): array
    {
        return Customer::query()
            ->select('customers.id', 'customers.name')
            ->join('invoices', 'invoices.customer_id', '=', 'customers.id')
            ->whereBetween('invoices.issue_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc(DB::raw('sum(invoices.total)'))
            ->limit(7)
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'revenue' => (float) Invoice::query()->where('customer_id', $customer->id)->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->sum('total'),
            ])->values()->all();
    }

    protected function agingReport(): array
    {
        $buckets = [
            ['period' => 'Current (1-30 days)', 'min' => 0, 'max' => 30, 'dotColor' => 'bg-green-500', 'textColor' => 'text-green-600', 'barColor' => 'bg-green-500'],
            ['period' => '31-60 days', 'min' => 31, 'max' => 60, 'dotColor' => 'bg-yellow-500', 'textColor' => 'text-yellow-600', 'barColor' => 'bg-yellow-500'],
            ['period' => '61-90 days', 'min' => 61, 'max' => 90, 'dotColor' => 'bg-orange-500', 'textColor' => 'text-orange-600', 'barColor' => 'bg-orange-500'],
            ['period' => '90+ days', 'min' => 91, 'max' => 9999, 'dotColor' => 'bg-red-500', 'textColor' => 'text-red-600', 'barColor' => 'bg-red-500'],
        ];

        return collect($buckets)->map(function (array $bucket): array {
            $query = Invoice::query()->outstanding();
            if ($bucket['max'] < 9999) {
                $query->whereRaw('DATEDIFF(CURDATE(), due_date) BETWEEN ? AND ?', [$bucket['min'], $bucket['max']]);
            } else {
                $query->whereRaw('DATEDIFF(CURDATE(), due_date) >= ?', [$bucket['min']]);
            }

            return [
                'period' => $bucket['period'],
                'count' => $query->count(),
                'amount' => (float) $query->sum('balance_due'),
                'dotColor' => $bucket['dotColor'],
                'textColor' => $bucket['textColor'],
                'barColor' => $bucket['barColor'],
            ];
        })->values()->all();
    }

    protected function averageCollectionDays($paymentQuery): int
    {
        $completed = $paymentQuery->where('status', 'completed')->get(['invoice_id', 'paid_at']);
        if ($completed->isEmpty()) {
            return 0;
        }

        $days = $completed->map(function ($payment): int {
            $invoice = Invoice::query()->withoutGlobalScopes()->find($payment->invoice_id);
            if (! $invoice?->issue_date || ! $payment->paid_at) {
                return 0;
            }

            return $invoice->issue_date->diffInDays($payment->paid_at);
        });

        return (int) round($days->avg());
    }
}
