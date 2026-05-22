<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Services\RadiusAccountingUsageService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function __construct(
        protected RadiusAccountingUsageService $radiusAccountingUsage,
    ) {}

    public function usage(): View
    {
        $tenantId = $this->tenantId();
        $from = now()->subMonthsNoOverflow(11)->startOfMonth();
        $to = now()->endOfMonth();

        $records = $this->radiusAccountingUsage->sessionsForTenant($tenantId, $from, $to);

        $usageReports = [
            'summary' => $this->usageSummary($records),
            'records' => $this->usageRecords($records),
            'chartData' => $this->usageChartData($records),
            'customerOptions' => Customer::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name', 'first_name', 'last_name', 'company_name', 'customer_type'])
                ->map(fn (Customer $customer): array => [
                    'value' => (string) $customer->id,
                    'label' => $customer->full_name,
                ])
                ->values(),
            'planOptions' => Plan::query()
                ->whereIn('id', Subscription::query()
                    ->where('tenant_id', $tenantId)
                    ->whereNotNull('plan_id')
                    ->select('plan_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Plan $plan): array => [
                    'value' => (string) $plan->id,
                    'label' => $plan->name,
                ])
                ->values(),
            'routerOptions' => Router::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Router $router): array => [
                    'value' => (string) $router->id,
                    'label' => $router->name,
                ])
                ->values(),
        ];

        return view('reports.usage', compact('usageReports'));
    }

    public function financial(): View
    {
        $tenantId = $this->tenantId();
        $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();

        $revenueThisMonth = $this->completedPaymentsBetween($tenantId, $thisMonth[0], $thisMonth[1])->sum('amount');
        $revenueLastMonth = $this->completedPaymentsBetween($tenantId, $lastMonthStart, $lastMonthEnd)->sum('amount');
        $activeSubscriptions = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $financialReports = [
            'summary' => [
                'revenueThisMonth' => (float) $revenueThisMonth,
                'revenueLastMonth' => (float) $revenueLastMonth,
                'revenueChangePercent' => $this->percentChange((float) $revenueThisMonth, (float) $revenueLastMonth),
                'outstandingBalance' => (float) Invoice::query()->where('tenant_id', $tenantId)->outstanding()->sum('balance_due'),
                'overdueAmount' => (float) Invoice::query()->where('tenant_id', $tenantId)->overdue()->sum('balance_due'),
                'arpu' => $activeSubscriptions > 0 ? (float) $revenueThisMonth / $activeSubscriptions : 0,
                'pendingInvoices' => Invoice::query()->where('tenant_id', $tenantId)->outstanding()->count(),
                'overdueInvoices' => Invoice::query()->where('tenant_id', $tenantId)->overdue()->count(),
            ],
            'revenueRecords' => $this->revenueRecords($tenantId),
            'topCustomers' => $this->topFinancialCustomers($tenantId),
            'paymentMethods' => $this->paymentMethods($tenantId),
            'revenueChartData' => $this->revenueChartData($tenantId),
        ];

        return view('reports.financial', compact('financialReports'));
    }

    private function tenantId(): string
    {
        return (string) (tenant()?->id ?? auth()->user()->tenant_id);
    }

    private function usageSummary(Collection $records): array
    {
        $totalUsage = (int) $records->sum('total');
        $customerCount = max(1, $records->pluck('customer_id')->unique()->count());
        $peakRecord = $records->sortByDesc('total')->first();

        return [
            'totalUsage' => $totalUsage,
            'avgUsage' => (int) round($totalUsage / $customerCount),
            'peakUsage' => (int) ($peakRecord['total'] ?? 0),
            'peakDate' => $peakRecord['last_activity_date_label'] ?? 'No usage yet',
            'activeUsers' => $records->pluck('customer_id')->unique()->count(),
        ];
    }

    private function usageRecords(Collection $records): Collection
    {
        return $records
            ->map(fn (array $record): array => [
                'id' => $record['id'],
                'period' => $record['last_activity_date_label'] ?? 'Unknown',
                'date' => $record['last_activity_date'],
                'customer' => $record['customer'],
                'customerId' => (string) $record['customer_id'],
                'planId' => $record['plan_id'] ? (string) $record['plan_id'] : '',
                'routerId' => $record['router_id'] ? (string) $record['router_id'] : '',
                'download' => $record['download'],
                'upload' => $record['upload'],
                'total' => $record['total'],
                'sessions' => 1,
            ])
            ->sortByDesc('date')
            ->values();
    }

    private function usageChartData(Collection $records): Collection
    {
        return $records
            ->groupBy(fn (array $record): string => filled($record['last_activity_date']) ? Carbon::parse($record['last_activity_date'])->format('Y-m') : 'unknown')
            ->map(function (Collection $group): array {
                $first = $group->first();
                $date = filled($first['last_activity_date'] ?? null) ? Carbon::parse($first['last_activity_date']) : null;

                return [
                    'period' => $date?->format('M') ?? 'N/A',
                    'download' => $group->sum('download'),
                    'upload' => $group->sum('upload'),
                ];
            })
            ->values();
    }

    private function completedPaymentsBetween(string $tenantId, Carbon $from, Carbon $to): Builder
    {
        return Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    private function revenueRecords(string $tenantId): Collection
    {
        return collect(range(6, 0))->map(function (int $offset) use ($tenantId): array {
            $monthStart = now()->startOfMonth()->subMonthsNoOverflow($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $issued = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
            $paidInvoices = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereBetween('issue_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
            $issuedCount = (clone $issued)->count();
            $paidCount = $paidInvoices->count();

            return [
                'id' => $monthStart->format('Ym'),
                'month' => $monthStart->format('F Y'),
                'invoicesIssued' => $issuedCount,
                'invoicesPaid' => $paidCount,
                'revenue' => (float) $this->completedPaymentsBetween($tenantId, $monthStart, $monthEnd)->sum('amount'),
                'outstanding' => (float) (clone $issued)->outstanding()->sum('balance_due'),
                'collectionRate' => $issuedCount > 0 ? (int) round(($paidCount / $issuedCount) * 100) : 0,
            ];
        });
    }

    private function topFinancialCustomers(string $tenantId): Collection
    {
        $paymentTotals = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->selectRaw('customer_id, sum(amount) as total_paid')
            ->groupBy('customer_id')
            ->orderByDesc('total_paid')
            ->limit(8)
            ->get()
            ->keyBy('customer_id');

        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->with(['subscriptions' => fn ($query) => $query->with('plan:id,name')->latest()])
            ->whereIn('id', $paymentTotals->keys())
            ->get()
            ->keyBy('id');

        $maxRevenue = max(1, (float) $paymentTotals->max('total_paid'));

        return $paymentTotals->map(function ($row) use ($customers, $maxRevenue): array {
            /** @var Customer|null $customer */
            $customer = $customers->get($row->customer_id);
            $subscription = $customer?->subscriptions->firstWhere('status', 'active') ?? $customer?->subscriptions->first();
            $totalPaid = (float) $row->total_paid;

            return [
                'id' => (int) $row->customer_id,
                'name' => $customer?->full_name ?? 'Unknown customer',
                'company' => $customer?->company_name ?? $customer?->customer_code ?? '',
                'plan' => $subscription?->plan?->name ?? 'No active plan',
                'totalPaid' => $totalPaid,
                'activeSubscription' => $subscription?->status === 'active',
                'trendLevel' => max(1, min(6, (int) ceil(($totalPaid / $maxRevenue) * 6))),
            ];
        })->values();
    }

    private function paymentMethods(string $tenantId): Collection
    {
        $methods = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->selectRaw("coalesce(payment_method, 'cash') as method, count(*) as total_count, sum(amount) as total_amount")
            ->groupBy('method')
            ->orderByDesc('total_amount')
            ->get();
        $total = max(1, (float) $methods->sum('total_amount'));
        $colors = [
            'bank_transfer' => ['bg-blue-100 text-blue-600', 'bg-blue-500'],
            'card' => ['bg-green-100 text-green-600', 'bg-green-500'],
            'credit_card' => ['bg-green-100 text-green-600', 'bg-green-500'],
            'online' => ['bg-purple-100 text-purple-600', 'bg-purple-500'],
            'cash' => ['bg-orange-100 text-orange-600', 'bg-orange-500'],
            'check' => ['bg-yellow-100 text-yellow-600', 'bg-yellow-500'],
        ];

        return $methods->map(function ($row, int $index) use ($colors, $total): array {
            $method = (string) $row->method;
            $color = $colors[$method] ?? ['bg-gray-100 text-gray-600', 'bg-gray-500'];

            return [
                'id' => $index + 1,
                'name' => str((string) $row->method)->replace('_', ' ')->title()->toString(),
                'amount' => (float) $row->total_amount,
                'count' => (int) $row->total_count,
                'percentage' => (int) round(((float) $row->total_amount / $total) * 100),
                'colorClass' => $color[0],
                'barColor' => $color[1],
            ];
        })->values();
    }

    private function revenueChartData(string $tenantId): Collection
    {
        return collect(range(5, 0))->map(function (int $offset) use ($tenantId): array {
            $monthStart = now()->startOfMonth()->subMonthsNoOverflow($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();

            return [
                'month' => $monthStart->format('M'),
                'revenue' => (float) $this->completedPaymentsBetween($tenantId, $monthStart, $monthEnd)->sum('amount'),
            ];
        });
    }

    private function percentChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
