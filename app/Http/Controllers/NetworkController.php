<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\NetworkAlert;
use App\Models\NetworkBandwidthSample;
use App\Models\Router;
use App\Models\RouterMonitoringState;
use App\Models\Subscription;
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
        $tenantId = $this->tenantId();
        $samples = NetworkBandwidthSample::query()
            ->where('tenant_id', $tenantId)
            ->with('router:id,name,ip_address')
            ->where('sampled_at', '>=', now()->subDay())
            ->orderBy('sampled_at')
            ->get();

        $latestByRouter = $samples
            ->sortByDesc('sampled_at')
            ->unique('router_id')
            ->values();

        $peakSample = $samples->sortByDesc(fn (NetworkBandwidthSample $sample): int => $sample->download_bps + $sample->upload_bps)->first();

        $networkBandwidth = [
            'stats' => [
                'totalThroughput' => $latestByRouter->sum(fn (NetworkBandwidthSample $sample): int => $sample->download_bps + $sample->upload_bps),
                'downloadThroughput' => $latestByRouter->sum('download_bps'),
                'uploadThroughput' => $latestByRouter->sum('upload_bps'),
                'peakUsage' => $peakSample ? $peakSample->download_bps + $peakSample->upload_bps : 0,
                'peakTime' => $peakSample?->sampled_at?->format('H:i') ?? '—',
            ],
            'chartData' => $this->bandwidthChartData($samples),
            'routerBandwidth' => $latestByRouter->map(fn (NetworkBandwidthSample $sample): array => $this->routerBandwidthRow($sample))->values(),
            'interfaces' => $latestByRouter->map(fn (NetworkBandwidthSample $sample): array => $this->interfaceRow($sample))->values(),
        ];

        return view('network.bandwidth', compact('networkBandwidth'));
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

    private function bandwidthChartData(Collection $samples): Collection
    {
        return $samples
            ->groupBy(fn (NetworkBandwidthSample $sample): string => $sample->sampled_at->format('H:00'))
            ->map(fn (Collection $hourSamples, string $time): array => [
                'time' => $time,
                'download' => (int) round($hourSamples->avg('download_bps')),
                'upload' => (int) round($hourSamples->avg('upload_bps')),
            ])
            ->values();
    }

    private function routerBandwidthRow(NetworkBandwidthSample $sample): array
    {
        $total = $sample->download_bps + $sample->upload_bps;
        $utilization = $sample->capacity_bps > 0 ? (int) round(($total / $sample->capacity_bps) * 100) : 0;

        return [
            'id' => $sample->router_id,
            'name' => $sample->router?->name ?? 'Unknown router',
            'ipAddress' => $sample->router?->ip_address ?? '—',
            'interface' => $sample->interface_name,
            'download' => $sample->download_bps,
            'upload' => $sample->upload_bps,
            'peak' => $total,
            'capacity' => $sample->capacity_bps,
            'utilization' => min($utilization, 100),
            'status' => $utilization > 80 ? 'critical' : ($utilization > 60 ? 'warning' : 'optimal'),
        ];
    }

    private function interfaceRow(NetworkBandwidthSample $sample): array
    {
        $usage = $sample->download_bps + $sample->upload_bps;
        $usagePercent = $sample->capacity_bps > 0 ? (int) round(($usage / $sample->capacity_bps) * 100) : 0;

        return [
            'id' => $sample->id,
            'name' => $sample->interface_name,
            'router' => $sample->router?->name ?? 'Unknown router',
            'capacity' => $sample->capacity_bps,
            'usage' => $usage,
            'usagePercent' => min($usagePercent, 100),
            'status' => $usagePercent > 80 ? 'error' : ($usagePercent > 60 ? 'warning' : 'active'),
        ];
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
