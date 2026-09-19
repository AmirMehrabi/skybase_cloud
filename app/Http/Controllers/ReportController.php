<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\FinancialReportRequest;
use App\Http\Requests\Report\UsageReportRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\RadiusAccountingUsageService;
use App\Services\SimplePdfReport;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected RadiusAccountingUsageService $radiusAccountingUsage,
        protected SimplePdfReport $pdfReport,
    ) {}

    public function usage(UsageReportRequest $request): View
    {
        $tenantId = $this->tenantId();
        [$from, $to] = $this->usageRange($request->validated());
        $records = $this->filteredUsageRecords($tenantId, $from, $to, $request->validated());
        $tenant = $this->tenant($tenantId);

        $usageReports = [
            'summary' => $this->usageSummary($records),
            'records' => $this->usageRecords($records),
            'chartData' => $this->usageChartData($records, (string) $request->input('group_by', 'day')),
            'filters' => [...$request->validated(), 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'timezone' => $tenant->timezone ?: config('app.timezone'),
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

    public function financial(FinancialReportRequest $request): View
    {
        $tenantId = $this->tenantId();
        [$from, $to] = $this->financialRange($request->validated());
        $previousFrom = $from->copy()->subDays($from->diffInDays($to) + 1);
        $previousTo = $from->copy()->subDay()->endOfDay();
        $tenant = $this->tenant($tenantId);

        $revenueThisMonth = $this->completedPaymentsBetween($tenantId, $from, $to)->sum('amount');
        $revenueLastMonth = $this->completedPaymentsBetween($tenantId, $previousFrom, $previousTo)->sum('amount');
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
            'revenueRecords' => $this->revenueRecords($tenantId, $from, $to),
            'topCustomers' => $this->topFinancialCustomers($tenantId, $from, $to),
            'paymentMethods' => $this->paymentMethods($tenantId, $from, $to),
            'revenueChartData' => $this->revenueChartData($tenantId, $from, $to),
            'filters' => [...$request->validated(), 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'currency' => $tenant->currency ?: 'USD',
            'timezone' => $tenant->timezone ?: config('app.timezone'),
        ];

        return view('reports.financial', compact('financialReports'));
    }

    public function usageCsv(UsageReportRequest $request): StreamedResponse
    {
        $tenantId = $this->tenantId();
        [$from, $to] = $this->usageRange($request->validated());
        $records = $this->usageRecords($this->filteredUsageRecords($tenantId, $from, $to, $request->validated()));

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['Date', 'Customer', 'Download bytes', 'Upload bytes', 'Total bytes', 'Sessions']);
            foreach ($records as $record) {
                fputcsv($stream, [$record['date'], $record['customer'], $record['download'], $record['upload'], $record['total'], $record['sessions']]);
            }
            fclose($stream);
        }, 'usage-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function usagePdf(UsageReportRequest $request): Response
    {
        $tenantId = $this->tenantId();
        [$from, $to] = $this->usageRange($request->validated());
        $records = $this->usageRecords($this->filteredUsageRecords($tenantId, $from, $to, $request->validated()));
        $summary = $this->usageSummary($records);
        $lines = ["Period: {$from->toDateString()} to {$to->toDateString()}", 'Total usage: '.$this->formatBytes((int) $summary['totalUsage']), ''];
        foreach ($records as $record) {
            $lines[] = sprintf('%-12s %-28s D: %-10s U: %-10s Sessions: %d', $record['date'], mb_strimwidth($record['customer'], 0, 28), $this->formatBytes($record['download']), $this->formatBytes($record['upload']), $record['sessions']);
        }

        return response($this->pdfReport->render('Usage Report', $lines), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="usage-report.pdf"']);
    }

    public function financialCsv(FinancialReportRequest $request): StreamedResponse
    {
        $tenantId = $this->tenantId();
        [$from, $to] = $this->financialRange($request->validated());
        $records = $this->revenueRecords($tenantId, $from, $to);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['Month', 'Invoices issued', 'Invoices paid', 'Revenue', 'Outstanding', 'Collection rate']);
            foreach ($records as $record) {
                fputcsv($stream, [$record['month'], $record['invoicesIssued'], $record['invoicesPaid'], $record['revenue'], $record['outstanding'], $record['collectionRate'].'%']);
            }
            fclose($stream);
        }, 'financial-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function financialPdf(FinancialReportRequest $request): Response
    {
        $tenantId = $this->tenantId();
        [$from, $to] = $this->financialRange($request->validated());
        $tenant = $this->tenant($tenantId);
        $records = $this->revenueRecords($tenantId, $from, $to);
        $lines = ["Period: {$from->toDateString()} to {$to->toDateString()}", 'Currency: '.($tenant->currency ?: 'USD'), ''];
        foreach ($records as $record) {
            $lines[] = sprintf('%-18s Issued: %-5d Paid: %-5d Revenue: %-12.2f Outstanding: %.2f', $record['month'], $record['invoicesIssued'], $record['invoicesPaid'], $record['revenue'], $record['outstanding']);
        }

        return response($this->pdfReport->render('Financial Report', $lines), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="financial-report.pdf"']);
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

    private function usageChartData(Collection $records, string $groupBy): Collection
    {
        return $records
            ->groupBy(fn (array $record): string => $this->periodKey((string) $record['last_activity_date'], $groupBy))
            ->map(function (Collection $group) use ($groupBy): array {
                $first = $group->first();
                $date = filled($first['last_activity_date'] ?? null) ? Carbon::parse($first['last_activity_date']) : null;

                return [
                    'period' => $date ? $this->periodLabel($date, $groupBy) : 'N/A',
                    'download' => $group->sum('download'),
                    'upload' => $group->sum('upload'),
                ];
            })
            ->values();
    }

    private function completedPaymentsBetween(string $tenantId, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    private function revenueRecords(string $tenantId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $months = collect();
        $cursor = Carbon::parse($from)->startOfMonth();
        while ($cursor->lte($to)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        return $months->map(function (Carbon $monthStart) use ($tenantId, $from, $to): array {
            $monthEnd = $monthStart->copy()->endOfMonth();
            $queryFrom = $monthStart->lt($from) ? Carbon::parse($from) : $monthStart;
            $queryTo = $monthEnd->gt($to) ? Carbon::parse($to) : $monthEnd;
            $issued = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->whereBetween('issue_date', [$queryFrom->toDateString(), $queryTo->toDateString()]);
            $paidInvoices = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereBetween('issue_date', [$queryFrom->toDateString(), $queryTo->toDateString()]);
            $issuedCount = (clone $issued)->count();
            $paidCount = $paidInvoices->count();

            return [
                'id' => $monthStart->format('Ym'),
                'month' => $monthStart->format('F Y'),
                'invoicesIssued' => $issuedCount,
                'invoicesPaid' => $paidCount,
                'revenue' => (float) $this->completedPaymentsBetween($tenantId, $queryFrom, $queryTo)->sum('amount'),
                'outstanding' => (float) (clone $issued)->outstanding()->sum('balance_due'),
                'collectionRate' => $issuedCount > 0 ? (int) round(($paidCount / $issuedCount) * 100) : 0,
            ];
        });
    }

    private function topFinancialCustomers(string $tenantId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $paymentTotals = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
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

    private function paymentMethods(string $tenantId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $methods = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
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

    private function revenueChartData(string $tenantId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->revenueRecords($tenantId, $from, $to)->map(fn (array $record): array => ['month' => Carbon::createFromFormat('F Y', $record['month'])->format('M y'), 'revenue' => $record['revenue']]);
    }

    /** @param array<string, mixed> $filters */
    private function filteredUsageRecords(string $tenantId, CarbonInterface $from, CarbonInterface $to, array $filters): Collection
    {
        return $this->radiusAccountingUsage->dailyUsageForTenant($tenantId, $from, $to)
            ->filter(function (array $record) use ($filters): bool {
                return (! filled($filters['customer_id'] ?? null) || (string) $record['customer_id'] === (string) $filters['customer_id'])
                    && (! filled($filters['plan_id'] ?? null) || (string) ($record['plan_id'] ?? '') === (string) $filters['plan_id'])
                    && (! filled($filters['router_id'] ?? null) || (string) ($record['router_id'] ?? '') === (string) $filters['router_id']);
            })
            ->values();
    }

    /** @param array<string, mixed> $filters @return array{Carbon, Carbon} */
    private function usageRange(array $filters): array
    {
        $range = (string) ($filters['range'] ?? 'month');

        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [Carbon::parse($filters['from'])->startOfDay(), Carbon::parse($filters['to'])->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /** @param array<string, mixed> $filters @return array{Carbon, Carbon} */
    private function financialRange(array $filters): array
    {
        $period = (string) ($filters['period'] ?? 'this_month');

        return match ($period) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [Carbon::parse($filters['from'])->startOfDay(), Carbon::parse($filters['to'])->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    private function tenant(string $tenantId): Tenant
    {
        return Tenant::query()->findOrFail($tenantId);
    }

    private function periodKey(string $date, string $groupBy): string
    {
        $value = Carbon::parse($date);

        return match ($groupBy) {
            'week' => $value->copy()->startOfWeek()->format('Y-m-d'),
            'month' => $value->format('Y-m'),
            'quarter' => $value->format('Y').'-Q'.$value->quarter,
            default => $value->format('Y-m-d'),
        };
    }

    private function periodLabel(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'week' => 'Week of '.$date->copy()->startOfWeek()->format('M j'),
            'month' => $date->format('M Y'),
            'quarter' => 'Q'.$date->quarter.' '.$date->year,
            default => $date->format('M j'),
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $index = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $index), 1).' '.$units[$index];
    }

    private function percentChange(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
