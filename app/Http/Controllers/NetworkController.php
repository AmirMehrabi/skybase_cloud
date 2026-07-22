<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\NetworkAlert;
use App\Models\Router;
use App\Models\RouterMonitoringState;
use App\Models\Subscription;
use App\Models\SubscriptionBandwidthState;
use App\Services\Monitoring\CustomerBandwidthUsageService;
use App\Services\Monitoring\RrdToolService;
use App\Services\RadiusAccountingUsageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NetworkController extends Controller
{
    public function __construct(
        protected RadiusAccountingUsageService $radiusAccountingUsage,
        protected CustomerBandwidthUsageService $customerBandwidthUsage,
    ) {}

    public function status(): View
    {
        $tenantId = $this->tenantId();

        $routers = Router::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->get()
            ->map(fn (Router $router): array => [
                'id' => $router->id,
                'name' => $router->name,
                'location' => $router->location ?: ($router->site ?: 'Unassigned site'),
                'ipAddress' => $router->ip_address,
                'status' => $this->routerDisplayStatus($router),
                'cpu' => (int) $router->cpu_usage,
                'memory' => (int) $router->memory_usage,
                'activeSessions' => (int) $router->active_sessions_count,
                'uptime' => $router->uptime,
                'lastSeen' => $router->status === 'online' ? 'Just now' : $router->updated_at?->diffForHumans(),
            ]);

        $alerts = NetworkAlert::query()
            ->where('tenant_id', $tenantId)
            ->with('router:id,name')
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(fn (NetworkAlert $alert): array => [
                'id' => $alert->id,
                'time' => $alert->occurred_at?->diffForHumans() ?? $alert->created_at?->diffForHumans(),
                'router' => $alert->router?->name ?? 'System',
                'severity' => $alert->severity,
                'message' => $alert->message,
            ]);

        $networkStatus = [
            'stats' => [
                'totalRouters' => $routers->count(),
                'onlineRouters' => $routers->where('status', 'online')->count(),
                'offlineRouters' => $routers->where('status', 'offline')->count(),
                'activeSessions' => $routers->sum('activeSessions'),
                'alerts' => NetworkAlert::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->count(),
            ],
            'routers' => $routers->values(),
            'alerts' => $alerts,
        ];

        return view('network.status', compact('networkStatus'));
    }

    public function bandwidth(): View
    {
        $networkBandwidth = $this->bandwidthPayload();

        return view('network.bandwidth', compact('networkBandwidth'));
    }

    public function bandwidthData(): JsonResponse
    {
        return response()->json($this->bandwidthPayload());
    }

    public function dataUsage(): View
    {
        $tenantId = $this->tenantId();
        $sessions = $this->radiusAccountingUsage->dailyUsageForTenant($tenantId, now()->subYear()->startOfDay(), now());
        $usageData = $sessions->map(fn (array $session): array => $this->usageRow($session))->values();
        $summary = $this->radiusAccountingUsage->summary($sessions);
        $todaySessions = $sessions->filter(fn (array $session): bool => ($session['last_activity_date'] ?? null) === now()->toDateString());
        $monthSessions = $sessions->filter(fn (array $session): bool => filled($session['last_activity_date']) && str_starts_with((string) $session['last_activity_date'], now()->format('Y-m')));
        $monthUsageData = $monthSessions->map(fn (array $session): array => $this->usageRow($session))->values();
        $monthSummary = $this->radiusAccountingUsage->summary($monthSessions);
        $topUser = $this->groupUsageRows($monthUsageData)->sortByDesc('total')->first();

        $networkUsage = [
            'stats' => [
                'totalToday' => (int) $todaySessions->sum('total'),
                'totalMonth' => (int) $monthSessions->sum('total'),
                'activeUsers' => $summary['onlineSessions'],
                'topUserUsage' => $topUser['total'] ?? 0,
                'topUserName' => $topUser['customer'] ?? 'No usage yet',
                'avgUsagePerUser' => $monthSummary['avgPerCustomer'],
            ],
            'routerOptions' => Router::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Router $router): array => ['value' => (string) $router->id, 'label' => $router->name]),
            'customerOptions' => Customer::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name', 'first_name', 'last_name', 'company_name', 'customer_type'])
                ->map(fn (Customer $customer): array => ['value' => (string) $customer->id, 'label' => $customer->full_name]),
            'subscriptionOptions' => Subscription::query()
                ->where('tenant_id', $tenantId)
                ->orderBy('subscription_code')
                ->get(['id', 'subscription_code'])
                ->map(fn (Subscription $subscription): array => ['value' => (string) $subscription->id, 'label' => $subscription->subscription_code]),
            'usageData' => $usageData,
        ];

        return view('network.data-usage', compact('networkUsage'));
    }

    public function monitoring(): View
    {
        $tenantId = $this->tenantId();
        $routers = Router::query()
            ->where('tenant_id', $tenantId)
            ->with('monitoringState')
            ->orderBy('name')
            ->get();

        $states = $routers->map(fn (Router $router): ?RouterMonitoringState => $router->monitoringState)->filter();
        $online = $states->where('status', 'online')->count();
        $warning = $states->where('status', 'warning')->count();
        $offline = max(0, $routers->count() - $online - $warning);
        $latest = $states->sortByDesc('sampled_at')->first();

        $monitoring = [
            'stats' => [
                'totalRouters' => $routers->count(),
                'onlineRouters' => $online,
                'warningRouters' => $warning,
                'offlineRouters' => $offline,
                'avgLatency' => (float) round((float) $states->whereNotNull('latency_ms')->avg('latency_ms'), 2),
                'avgPacketLoss' => (float) round((float) $states->whereNotNull('packet_loss_percent')->avg('packet_loss_percent'), 2),
                'lastSampledAt' => $latest?->sampled_at?->diffForHumans() ?? 'No samples yet',
                'rrdAvailable' => app(RrdToolService::class)->isAvailable(),
            ],
            'routers' => $routers->map(fn (Router $router): array => $this->routerMonitoringRow($router))->values(),
        ];

        return view('network.monitoring', compact('monitoring'));
    }

    public function monitoringData(Request $request, RrdToolService $rrdTool): JsonResponse
    {
        $tenantId = $this->tenantId();
        $range = (string) $request->query('range', '24h');
        $routerId = $request->query('router_id');

        $routers = Router::query()
            ->where('tenant_id', $tenantId)
            ->when($routerId, fn ($query) => $query->whereKey($routerId))
            ->orderBy('name')
            ->get();

        $series = $routers->flatMap(function (Router $router) use ($rrdTool, $range): array {
            try {
                return $rrdTool->routerHealthSeries($router, $range);
            } catch (\Throwable) {
                return [];
            }
        });

        $chartData = $series
            ->groupBy('timestamp')
            ->map(function (Collection $rows, int|string $timestamp): array {
                return [
                    'timestamp' => (int) $timestamp,
                    'time' => date('H:i', (int) $timestamp),
                    'latency_ms' => $this->averageNullable($rows, 'latency_ms'),
                    'packet_loss_percent' => $this->averageNullable($rows, 'packet_loss_percent'),
                    'online_percent' => $this->averageNullable($rows, 'online') !== null ? round((float) $this->averageNullable($rows, 'online') * 100, 2) : null,
                    'cpu_usage' => $this->averageNullable($rows, 'cpu_usage'),
                    'memory_usage' => $this->averageNullable($rows, 'memory_usage'),
                ];
            })
            ->values();

        return response()->json([
            'range' => $range,
            'chartData' => $chartData,
        ]);
    }

    private function tenantId(): string
    {
        return (string) (tenant()?->id ?? auth()->user()->tenant_id);
    }

    private function routerMonitoringRow(Router $router): array
    {
        $state = $router->monitoringState;

        return [
            'id' => $router->id,
            'name' => $router->name,
            'ipAddress' => $router->ip_address,
            'site' => $router->siteRecord?->name ?? $router->site ?? 'Unassigned site',
            'status' => $state?->status ?? $router->status ?? 'offline',
            'latencyMs' => $state?->latency_ms,
            'packetLossPercent' => $state?->packet_loss_percent,
            'uptime' => $state?->uptime ?? $router->uptime,
            'cpuUsage' => $state?->cpu_usage ?? $router->cpu_usage,
            'memoryUsage' => $state?->memory_usage ?? $router->memory_usage,
            'activeSessions' => $state?->active_sessions_count ?? $router->active_sessions_count,
            'sampledAt' => $state?->sampled_at?->diffForHumans() ?? 'No sample yet',
            'error' => $state?->error,
            'url' => route('routers.show', $router),
        ];
    }

    private function averageNullable(Collection $rows, string $key): ?float
    {
        $values = $rows->pluck($key)->filter(fn ($value): bool => $value !== null);

        if ($values->isEmpty()) {
            return null;
        }

        return round((float) $values->avg(), 2);
    }

    private function routerDisplayStatus(Router $router): string
    {
        if ($router->status === 'offline') {
            return 'offline';
        }

        return $router->cpu_usage > 80 || $router->memory_usage > 80 ? 'warning' : 'online';
    }

    /**
     * @return array<string, mixed>
     */
    private function bandwidthPayload(): array
    {
        $tenantId = $this->tenantId();
        $subscriptions = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->with('plan')
            ->orderBy('id')
            ->get();
        $states = SubscriptionBandwidthState::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('subscription', fn ($query) => $query->where('status', 'active'))
            ->with([
                'router:id,name,ip_address',
                'subscription:id,subscription_code,plan_id',
                'subscription.plan:id,name,download_speed,upload_speed,bandwidth_unit',
            ])
            ->get();
        $routers = Router::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'ip_address']);
        $byRouter = $states->groupBy('router_id');
        $routerBandwidth = $routers->map(function (Router $router) use ($byRouter): array {
            $routerStates = $byRouter->get($router->id, collect());
            $download = (int) $routerStates->sum('rx_bps');
            $upload = (int) $routerStates->sum('tx_bps');
            $capacity = (int) $routerStates->sum(fn (SubscriptionBandwidthState $state): int => $this->planCapacity($state));
            $lastSample = $routerStates->sortByDesc('sampled_at')->first();

            return $this->routerBandwidthRow($router, $download, $upload, $capacity, $lastSample);
        })->values();
        $interfaceRows = $states
            ->map(fn (SubscriptionBandwidthState $state): array => $this->interfaceRow($state))
            ->values();
        $history = $this->customerBandwidthUsage->aggregate($subscriptions, '24h');
        $latest = $states->sortByDesc('sampled_at')->first();
        $download = (int) $routerBandwidth->sum('download');
        $upload = (int) $routerBandwidth->sum('upload');
        $peak = collect($history['chartData'])->sortByDesc(fn (array $point): float => (float) ($point['total_bps'] ?? 0))->first();

        return [
            'stats' => [
                'totalThroughput' => $download + $upload,
                'downloadThroughput' => $download,
                'uploadThroughput' => $upload,
                'peakUsage' => (int) ($peak['total_bps'] ?? 0),
                'peakTime' => $peak['time'] ?? '—',
                'lastSampledAt' => $latest?->sampled_at?->diffForHumans() ?? 'No samples yet',
                'rrdAvailable' => app(RrdToolService::class)->isAvailable(),
            ],
            'chartData' => $history['chartData'],
            'hasData' => $history['hasData'],
            'routerBandwidth' => $routerBandwidth,
            'interfaces' => $interfaceRows,
        ];
    }

    private function routerBandwidthRow(Router $router, int $download, int $upload, int $capacity, ?SubscriptionBandwidthState $state): array
    {
        $total = $download + $upload;
        $utilization = $capacity > 0 ? (int) round(($total / $capacity) * 100) : null;

        return [
            'id' => $router->id,
            'name' => $router->name,
            'ipAddress' => $router->ip_address ?? '—',
            'interface' => $state?->interface_name ?? '—',
            'download' => $download,
            'upload' => $upload,
            'peak' => $total,
            'capacity' => $capacity,
            'utilization' => $utilization,
            'sampledAt' => $state?->sampled_at?->diffForHumans(),
            'status' => $this->bandwidthStatus($state, $utilization),
        ];
    }

    private function interfaceRow(SubscriptionBandwidthState $state): array
    {
        $usage = (int) $state->rx_bps + (int) $state->tx_bps;
        $capacity = $this->planCapacity($state);
        $usagePercent = $capacity > 0 ? (int) round(($usage / $capacity) * 100) : null;

        return [
            'id' => $state->id,
            'name' => $state->interface_name ?? 'Subscription interface',
            'subscription' => $state->subscription?->subscription_code ?? '—',
            'router' => $state->router?->name ?? 'Unknown router',
            'capacity' => $capacity,
            'usage' => $usage,
            'usagePercent' => $usagePercent,
            'sampledAt' => $state->sampled_at?->diffForHumans(),
            'status' => $this->bandwidthStatus($state, $usagePercent),
        ];
    }

    private function planCapacity(SubscriptionBandwidthState $state): int
    {
        $plan = $state->subscription?->plan;

        if (! $plan) {
            return 0;
        }

        return $this->speedToBps($plan->download_speed, $plan->bandwidth_unit)
            + $this->speedToBps($plan->upload_speed, $plan->bandwidth_unit);
    }

    private function speedToBps(int|float|null $speed, ?string $unit): int
    {
        return (int) round((float) $speed * match (strtolower((string) $unit)) {
            'kbps', 'kbit/s' => 1_000,
            'gbps', 'gbit/s' => 1_000_000_000,
            'tbps', 'tbit/s' => 1_000_000_000_000,
            default => 1_000_000,
        });
    }

    private function bandwidthStatus(?SubscriptionBandwidthState $state, ?int $utilization): string
    {
        if (! $state || $state->error || ! $state->last_success_at || $state->last_success_at->lte(now()->subMinutes(3))) {
            return 'unavailable';
        }

        return $utilization !== null && $utilization > 80 ? 'critical' : ($utilization !== null && $utilization > 60 ? 'warning' : 'optimal');
    }

    private function usageRow(array $session): array
    {
        return [
            'id' => $session['id'],
            'customer' => $session['customer'],
            'customerId' => (string) $session['customer_id'],
            'customerCode' => $session['customer_code'],
            'subscription' => $session['subscription'],
            'subscriptionId' => (string) $session['subscription_id'],
            'router' => $session['router'],
            'routerId' => $session['router_id'] ? (string) $session['router_id'] : '',
            'ipAddress' => $session['ip_address'],
            'download' => $session['download'],
            'upload' => $session['upload'],
            'total' => $session['total'],
            'usage' => $session['total'],
            'maxUsage' => max($session['download'], $session['upload'], 1),
            'quota' => $session['quota'],
            'sessionTime' => $session['duration'],
            'sessionSeconds' => $session['duration_seconds'],
            'sessions' => $session['sessions'] ?? 1,
            'lastActivity' => $session['last_activity'],
            'lastActivityDate' => $session['last_activity_date'],
            'plan' => $session['plan'],
        ];
    }

    private function groupUsageRows(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row): string => implode(':', [$row['customerId'], $row['subscriptionId'], $row['routerId']]))
            ->map(function (Collection $rows, string $key): array {
                $first = $rows->first();
                $download = (int) $rows->sum('download');
                $upload = (int) $rows->sum('upload');
                $total = $download + $upload;
                $last = $rows->sortByDesc('lastActivityDate')->first();

                return [
                    ...$first,
                    'id' => $key,
                    'download' => $download,
                    'upload' => $upload,
                    'total' => $total,
                    'usage' => $total,
                    'maxUsage' => max($download, $upload, 1),
                    'sessionSeconds' => (int) $rows->sum('sessionSeconds'),
                    'sessionTime' => $this->radiusAccountingUsage->formatDuration((int) $rows->sum('sessionSeconds')),
                    'sessions' => (int) $rows->sum('sessions'),
                    'lastActivity' => $last['lastActivity'] ?? 'No usage yet',
                    'lastActivityDate' => $last['lastActivityDate'] ?? null,
                ];
            })
            ->values();
    }
}
