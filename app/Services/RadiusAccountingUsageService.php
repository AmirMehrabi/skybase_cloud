<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RadiusAccountingUsageService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedSessionsForSubscription(
        Subscription $subscription,
        array $filters,
        int $perPage = 25,
        string $pageName = 'usage_page',
    ): LengthAwarePaginator {
        if (! $this->canReadAccounting() || blank($subscription->pppoe_username)) {
            return new LengthAwarePaginator([], 0, $perPage, 1, [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]);
        }

        $subscription->loadMissing(['customer', 'plan', 'router']);
        $query = $this->accountingQuery(collect([(string) $subscription->pppoe_username]));

        $this->applySessionFilters($query, $filters);

        $paginator = $query
            ->orderByDesc(DB::raw('coalesce(acctstoptime, acctupdatetime, acctstarttime)'))
            ->paginate($perPage, ['*'], $pageName);
        $routers = $this->routersForTenant((string) $subscription->tenant_id);

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (object $session): array => $this->sessionRow(
                    $session,
                    $subscription,
                    $subscription->customer,
                    $subscription->plan,
                    $subscription->router ?? $routers->get($session->nasipaddress),
                )),
        );

        return $paginator;
    }

    /**
     * @return array{range: string, from: string, to: string, bucket: string, labels: list<string>, download: list<int>, upload: list<int>, total_download: int, total_upload: int}
     */
    public function usageChartForSubscription(
        Subscription $subscription,
        string $range,
        ?CarbonInterface $customFrom = null,
        ?CarbonInterface $customTo = null,
    ): array {
        [$from, $to, $bucket] = $this->chartWindow($range, $customFrom, $customTo);
        $empty = [
            'range' => $range,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'bucket' => $bucket,
            'labels' => [],
            'download' => [],
            'upload' => [],
            'total_download' => 0,
            'total_upload' => 0,
        ];

        if (! $this->canReadAccounting() || blank($subscription->pppoe_username)) {
            return $empty;
        }

        $activityExpression = 'coalesce(acctstarttime, acctupdatetime, acctstoptime)';
        $bucketExpression = $this->bucketExpression($activityExpression, $bucket);
        $downloadExpression = '(coalesce(acctoutputoctets, 0) + (coalesce(acctoutputgigawords, 0) * 4294967296))';
        $uploadExpression = '(coalesce(acctinputoctets, 0) + (coalesce(acctinputgigawords, 0) * 4294967296))';

        $rows = $this->accountingQuery(collect([(string) $subscription->pppoe_username]))
            ->whereBetween(DB::raw($activityExpression), [$from, $to])
            ->selectRaw("{$bucketExpression} as usage_bucket")
            ->selectRaw("sum({$downloadExpression}) as download_bytes")
            ->selectRaw("sum({$uploadExpression}) as upload_bytes")
            ->groupByRaw($bucketExpression)
            ->orderBy('usage_bucket')
            ->get();

        return [
            ...$empty,
            'labels' => $rows
                ->map(fn (object $row): string => $this->bucketLabel((string) $row->usage_bucket, $bucket))
                ->values()
                ->all(),
            'download' => $rows->map(fn (object $row): int => (int) $row->download_bytes)->values()->all(),
            'upload' => $rows->map(fn (object $row): int => (int) $row->upload_bytes)->values()->all(),
            'total_download' => (int) $rows->sum('download_bytes'),
            'total_upload' => (int) $rows->sum('upload_bytes'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function sessionsForSubscription(Subscription $subscription, ?CarbonInterface $from = null, ?CarbonInterface $to = null, int $limit = 25): Collection
    {
        if (! $this->canReadAccounting() || blank($subscription->pppoe_username)) {
            return collect();
        }

        $subscription->loadMissing(['customer', 'plan', 'router']);

        $sessions = $this->accountingQuery(collect([(string) $subscription->pppoe_username]), $from, $to)
            ->orderByDesc(DB::raw('coalesce(acctstoptime, acctupdatetime, acctstarttime)'))
            ->limit($limit)
            ->get();

        $routers = $this->routersForTenant((string) $subscription->tenant_id);

        return $sessions
            ->map(fn (object $session): array => $this->sessionRow(
                $session,
                $subscription,
                $subscription->customer,
                $subscription->plan,
                $subscription->router ?? $routers->get($session->nasipaddress),
            ))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function sessionsForTenant(string $tenantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        $subscriptions = $this->pppoeSubscriptionsForTenant($tenantId);

        if (! $this->canReadAccounting() || $subscriptions->isEmpty()) {
            return collect();
        }

        $subscriptionsByUsername = $subscriptions->keyBy(fn (Subscription $subscription): string => (string) $subscription->pppoe_username);
        $routers = $this->routersForTenant($tenantId);

        return $this->accountingQuery($subscriptionsByUsername->keys(), $from, $to)
            ->orderByDesc(DB::raw('coalesce(acctstoptime, acctupdatetime, acctstarttime)'))
            ->get()
            ->map(function (object $session) use ($subscriptionsByUsername, $routers): ?array {
                $subscription = $subscriptionsByUsername->get((string) $session->username);

                if (! $subscription instanceof Subscription) {
                    return null;
                }

                return $this->sessionRow(
                    $session,
                    $subscription,
                    $subscription->customer,
                    $subscription->plan,
                    $subscription->router ?? $routers->get($session->nasipaddress),
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function groupedUsageForTenant(string $tenantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return $this->sessionsForTenant($tenantId, $from, $to)
            ->groupBy(fn (array $session): string => implode(':', [
                $session['customer_id'],
                $session['subscription_id'],
                $session['router_id'] ?: $session['nas_ip'],
            ]))
            ->map(function (Collection $sessions, string $key): array {
                $first = $sessions->first();
                $download = (int) $sessions->sum('download');
                $upload = (int) $sessions->sum('upload');
                $total = $download + $upload;

                return [
                    'id' => $key,
                    'customer' => $first['customer'],
                    'customerId' => (string) $first['customer_id'],
                    'customerCode' => $first['customer_code'],
                    'subscription' => $first['subscription'],
                    'subscriptionId' => (string) $first['subscription_id'],
                    'router' => $first['router'],
                    'routerId' => (string) ($first['router_id'] ?? ''),
                    'ipAddress' => $first['ip_address'],
                    'download' => $download,
                    'upload' => $upload,
                    'total' => $total,
                    'usage' => $total,
                    'maxUsage' => max($download, $upload, 1),
                    'quota' => $first['quota'],
                    'sessionTime' => $this->formatDuration((int) $sessions->sum('duration_seconds')),
                    'sessionSeconds' => (int) $sessions->sum('duration_seconds'),
                    'sessions' => $sessions->count(),
                    'lastActivity' => $sessions->max('last_activity_sort') ?? 'No usage yet',
                    'lastActivityLabel' => $first['last_activity'],
                    'plan' => $first['plan'],
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Collection $sessions): array
    {
        $download = (int) $sessions->sum('download');
        $upload = (int) $sessions->sum('upload');
        $total = $download + $upload;
        $customers = $sessions->pluck('customer_id')->filter()->unique();
        $peak = $sessions->sortByDesc('total')->first();

        return [
            'download' => $download,
            'upload' => $upload,
            'total' => $total,
            'avgPerCustomer' => $customers->count() > 0 ? (int) round($total / $customers->count()) : 0,
            'peakUsage' => (int) ($peak['total'] ?? 0),
            'peakDate' => $peak['last_activity_date_label'] ?? 'No usage yet',
            'activeUsers' => $customers->count(),
            'onlineSessions' => $sessions->where('status', 'online')->count(),
        ];
    }

    private function canReadAccounting(): bool
    {
        return Schema::hasTable('radacct')
            && Schema::hasColumn('radacct', 'username')
            && Schema::hasColumn('radacct', 'acctstoptime');
    }

    /**
     * @return Collection<string, Subscription>
     */
    private function pppoeSubscriptionsForTenant(string $tenantId): Collection
    {
        return Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('connection_type', 'pppoe')
            ->whereNotNull('pppoe_username')
            ->with([
                'customer:id,customer_code,name,first_name,last_name,company_name,customer_type',
                'plan:id,name,data_limit,data_unit,unlimited',
                'router:id,name,ip_address',
            ])
            ->get();
    }

    /**
     * @param  Collection<int, string>|Collection<string, string>  $usernames
     */
    private function accountingQuery(Collection $usernames, ?CarbonInterface $from = null, ?CarbonInterface $to = null)
    {
        $query = DB::table('radacct')
            ->whereIn('username', $usernames->filter()->values());

        if ($from !== null || $to !== null) {
            $from ??= now()->subYears(10);
            $to ??= now();

            $query->where(function ($query) use ($from, $to): void {
                foreach (['acctstarttime', 'acctupdatetime', 'acctstoptime'] as $column) {
                    if (Schema::hasColumn('radacct', $column)) {
                        $query->orWhereBetween($column, [$from, $to]);
                    }
                }
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySessionFilters(mixed $query, array $filters): void
    {
        if (filled($filters['session_started_from'] ?? null)) {
            $query->where('acctstarttime', '>=', Carbon::parse($filters['session_started_from'])->startOfDay());
        }

        if (filled($filters['session_stopped_to'] ?? null)) {
            $query->where(
                DB::raw('coalesce(acctstoptime, acctupdatetime, acctstarttime)'),
                '<=',
                Carbon::parse($filters['session_stopped_to'])->endOfDay(),
            );
        }

        if (($filters['session_status'] ?? null) === 'online') {
            $query->whereNull('acctstoptime');
        } elseif (($filters['session_status'] ?? null) === 'offline') {
            $query->whereNotNull('acctstoptime');
        }

        foreach ([
            'session_nas_ip' => 'nasipaddress',
            'session_ip_address' => 'framedipaddress',
            'session_terminate_cause' => 'acctterminatecause',
        ] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) {
                $query->where($column, 'like', '%'.trim((string) $filters[$filter]).'%');
            }
        }
    }

    /**
     * @return array{CarbonInterface, CarbonInterface, string}
     */
    private function chartWindow(
        string $range,
        ?CarbonInterface $customFrom,
        ?CarbonInterface $customTo,
    ): array {
        if ($range === 'custom' && $customFrom && $customTo) {
            $from = Carbon::parse($customFrom)->startOfDay();
            $to = Carbon::parse($customTo)->endOfDay();
            $bucket = $from->diffInDays($to) <= 2 ? 'hour' : 'day';

            return [$from, $to, $bucket];
        }

        return match ($range) {
            'daily' => [now()->startOfDay(), now()->endOfDay(), 'hour'],
            'weekly' => [now()->subDays(6)->startOfDay(), now()->endOfDay(), 'day'],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay(), 'day'],
        };
    }

    private function bucketExpression(string $activityExpression, string $bucket): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return $bucket === 'hour'
                ? "strftime('%Y-%m-%d %H:00:00', {$activityExpression})"
                : "strftime('%Y-%m-%d', {$activityExpression})";
        }

        return $bucket === 'hour'
            ? "date_format({$activityExpression}, '%Y-%m-%d %H:00:00')"
            : "date({$activityExpression})";
    }

    private function bucketLabel(string $bucketValue, string $bucket): string
    {
        $date = Carbon::parse($bucketValue);

        return $bucket === 'hour'
            ? $date->format('H:i')
            : $date->format('M j');
    }

    /**
     * @return Collection<string, Router>
     */
    private function routersForTenant(string $tenantId): Collection
    {
        return Router::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('ip_address')
            ->get(['id', 'name', 'ip_address'])
            ->keyBy('ip_address');
    }

    private function sessionRow(object $session, Subscription $subscription, ?Customer $customer, ?Plan $plan, ?Router $router): array
    {
        $download = $this->octets($session, 'acctoutputoctets', 'acctoutputgigawords');
        $upload = $this->octets($session, 'acctinputoctets', 'acctinputgigawords');
        $total = $download + $upload;
        $startedAt = $this->dateValue($session->acctstarttime ?? null);
        $stoppedAt = $this->dateValue($session->acctstoptime ?? null);
        $updatedAt = $this->dateValue($session->acctupdatetime ?? null);
        $lastActivity = $stoppedAt ?? $updatedAt ?? $startedAt;
        $duration = (int) ($session->acctsessiontime ?? 0);

        if ($duration <= 0 && $startedAt !== null && $lastActivity !== null) {
            $duration = max(0, $startedAt->diffInSeconds($lastActivity));
        }

        return [
            'id' => (int) ($session->radacctid ?? 0),
            'session_id' => (string) ($session->acctsessionid ?? ''),
            'unique_id' => (string) ($session->acctuniqueid ?? ''),
            'username' => (string) ($session->username ?? ''),
            'customer' => $customer?->full_name ?? 'Unknown customer',
            'customer_id' => $customer?->id,
            'customer_code' => $customer?->customer_code ?? '-',
            'subscription' => $subscription->subscription_code,
            'subscription_id' => $subscription->id,
            'plan' => $plan?->name ?? 'Unassigned plan',
            'plan_id' => $plan?->id,
            'router' => $router?->name ?? ($session->nasipaddress ?? 'Unknown NAS'),
            'router_id' => $router?->id,
            'nas_ip' => $session->nasipaddress ?? '',
            'ip_address' => $session->framedipaddress ?? $subscription->ip_address ?? '-',
            'calling_station_id' => $session->callingstationid ?? '',
            'started_at' => $startedAt?->toDateTimeString(),
            'started_at_label' => $startedAt?->format('M d, Y H:i') ?? '-',
            'stopped_at' => $stoppedAt?->toDateTimeString(),
            'stopped_at_label' => $stoppedAt?->format('M d, Y H:i') ?? 'Online',
            'last_activity' => $lastActivity?->diffForHumans() ?? 'No activity',
            'last_activity_sort' => $lastActivity?->toDateTimeString(),
            'last_activity_date' => $lastActivity?->toDateString(),
            'last_activity_date_label' => $lastActivity?->format('M j, Y') ?? 'No usage yet',
            'duration' => $this->formatDuration($duration),
            'duration_seconds' => $duration,
            'download' => $download,
            'upload' => $upload,
            'total' => $total,
            'download_label' => $this->formatBytes($download),
            'upload_label' => $this->formatBytes($upload),
            'total_label' => $this->formatBytes($total),
            'quota' => $this->quotaBytes($plan),
            'status' => $stoppedAt === null ? 'online' : 'offline',
            'terminate_cause' => $session->acctterminatecause ?? '-',
        ];
    }

    private function octets(object $session, string $octetsColumn, string $gigawordsColumn): int
    {
        return (int) ($session->{$octetsColumn} ?? 0) + ((int) ($session->{$gigawordsColumn} ?? 0) * 4294967296);
    }

    private function dateValue(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    private function quotaBytes(?Plan $plan): int
    {
        if (! $plan || $plan->unlimited || ! $plan->data_limit) {
            return 1099511627776;
        }

        return match ($plan->data_unit) {
            'MB' => (int) round((float) $plan->data_limit * 1048576),
            'TB' => (int) round((float) $plan->data_limit * 1099511627776),
            default => (int) round((float) $plan->data_limit * 1073741824),
        };
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $size = (float) $bytes;
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return number_format($size, $index === 0 ? 0 : 2).' '.$units[$index];
    }

    public function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return $hours.'h '.($minutes > 0 ? $minutes.'m' : '');
        }

        return $minutes.'m';
    }
}
