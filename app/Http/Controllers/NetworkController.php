<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\NetworkAlert;
use App\Models\NetworkBandwidthSample;
use App\Models\NetworkUsageRecord;
use App\Models\Router;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class NetworkController extends Controller
{
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

        $usageRecords = NetworkUsageRecord::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'customer:id,customer_code,name,first_name,last_name,company_name,customer_type',
                'subscription:id,subscription_code,plan_id,ip_address',
                'subscription.plan:id,name,data_limit,data_unit,unlimited',
                'router:id,name',
            ])
            ->latest('last_activity_at')
            ->limit(200)
            ->get();

        $usageData = $usageRecords->map(fn (NetworkUsageRecord $record): array => $this->usageRow($record))->values();
        $topUser = $usageData->sortByDesc('total')->first();

        $networkUsage = [
            'stats' => [
                'totalToday' => NetworkUsageRecord::query()
                    ->where('tenant_id', $tenantId)
                    ->where('last_activity_at', '>=', now()->startOfDay())
                    ->get()
                    ->sum(fn (NetworkUsageRecord $record): int => $record->download_bytes + $record->upload_bytes),
                'totalMonth' => NetworkUsageRecord::query()
                    ->where('tenant_id', $tenantId)
                    ->where('last_activity_at', '>=', now()->startOfMonth())
                    ->get()
                    ->sum(fn (NetworkUsageRecord $record): int => $record->download_bytes + $record->upload_bytes),
                'activeUsers' => Subscription::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->count(),
                'topUserUsage' => $topUser['total'] ?? 0,
                'topUserName' => $topUser['customer'] ?? 'No usage yet',
                'avgUsagePerUser' => (int) round($usageData->avg('total') ?? 0),
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

    private function tenantId(): string
    {
        return (string) (tenant()?->id ?? auth()->user()->tenant_id);
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

    private function usageRow(NetworkUsageRecord $record): array
    {
        $total = $record->download_bytes + $record->upload_bytes;
        $quota = $this->quotaBytes($record->subscription?->plan);

        return [
            'id' => $record->id,
            'customer' => $record->customer?->full_name ?? 'Unknown customer',
            'customerId' => (string) $record->customer_id,
            'customerCode' => $record->customer?->customer_code ?? '—',
            'subscription' => $record->subscription?->subscription_code ?? 'No subscription',
            'subscriptionId' => $record->subscription_id ? (string) $record->subscription_id : '',
            'router' => $record->router?->name ?? 'No router',
            'routerId' => $record->router_id ? (string) $record->router_id : '',
            'ipAddress' => $record->ip_address ?: ($record->subscription?->ip_address ?? '—'),
            'download' => $record->download_bytes,
            'upload' => $record->upload_bytes,
            'total' => $total,
            'usage' => $total,
            'maxUsage' => max($record->download_bytes, $record->upload_bytes, 1),
            'quota' => $quota,
            'sessionTime' => $this->formatDuration($record->session_seconds),
            'lastActivity' => $record->last_activity_at?->diffForHumans() ?? '—',
            'plan' => $record->subscription?->plan?->name ?? 'Unassigned plan',
        ];
    }

    private function quotaBytes($plan): int
    {
        if (! $plan || $plan->unlimited || ! $plan->data_limit) {
            return 1099511627776;
        }

        $multiplier = match ($plan->data_unit) {
            'MB' => 1048576,
            'TB' => 1099511627776,
            default => 1073741824,
        };

        return (int) $plan->data_limit * $multiplier;
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return "{$hours}h {$minutes}m";
    }
}
