<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\IpPool;
use App\Models\Router;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $tenant = $this->resolveTenant();
        $tenantId = (string) $tenant->id;

        $customerCount = Customer::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $activeSubscriptionsCount = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $activeRecurringValue = (float) Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->sum('total_price');

        $routerTotals = [
            'online' => Router::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'online')
                ->count(),
            'total' => Router::query()
                ->where('tenant_id', $tenantId)
                ->count(),
        ];

        $poolsNearCapacityCount = IpPool::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query): void {
                $query->where('status', 'exhausted')
                    ->orWhereRaw('(used_ips / NULLIF(total_ips, 0)) * 100 >= ?', [80]);
            })
            ->count();

        $dashboard = [
            'tenant' => [
                'name' => $tenant->company_name ?: $tenant->name,
                'timezone' => $tenant->timezone,
                'currency' => $tenant->currency ?? 'USD',
                'status' => $tenant->status,
                'is_on_trial' => $tenant->isOnTrial(),
                'trial_ends_at' => $tenant->trial_ends_at?->toFormattedDateString(),
            ],
            'stats' => [
                'customers' => [
                    'value' => $customerCount,
                    'label' => 'Customers',
                    'meta' => $this->growthMeta(
                        Customer::query()->where('tenant_id', $tenantId),
                        'customer',
                    ),
                    'href' => route('customers.index'),
                ],
                'subscriptions' => [
                    'value' => $activeSubscriptionsCount,
                    'label' => 'Active subscriptions',
                    'meta' => $this->growthMeta(
                        Subscription::query()->where('tenant_id', $tenantId),
                        'subscription',
                    ),
                    'href' => route('subscriptions.index'),
                ],
                'recurring_value' => [
                    'value' => $this->formatMoney($activeRecurringValue, $tenant->currency ?? 'USD'),
                    'label' => 'Recurring subscription value',
                    'meta' => $activeSubscriptionsCount > 0
                        ? $this->formatMoney($activeRecurringValue / $activeSubscriptionsCount, $tenant->currency ?? 'USD').' average per active service'
                        : 'No active subscriptions yet',
                    'href' => route('subscriptions.index'),
                ],
                'routers' => [
                    'value' => $routerTotals['total'] > 0
                        ? "{$routerTotals['online']} / {$routerTotals['total']}"
                        : '0',
                    'label' => 'Routers online',
                    'meta' => $routerTotals['total'] > 0
                        ? $routerTotals['total'] - $routerTotals['online'].' offline'
                        : 'Add your first router to monitor network health',
                    'href' => route('routers.index'),
                ],
            ],
            'attention' => [
                [
                    'title' => 'Pending activations',
                    'value' => Subscription::query()
                        ->where('tenant_id', $tenantId)
                        ->where('status', 'pending')
                        ->count(),
                    'description' => 'Subscriptions waiting to go live',
                    'href' => route('subscriptions.index'),
                    'tone' => 'amber',
                ],
                [
                    'title' => 'Suspended services',
                    'value' => Subscription::query()
                        ->where('tenant_id', $tenantId)
                        ->where('status', 'suspended')
                        ->count(),
                    'description' => 'Customers currently disconnected',
                    'href' => route('subscriptions.index'),
                    'tone' => 'rose',
                ],
                [
                    'title' => 'Offline routers',
                    'value' => Router::query()
                        ->where('tenant_id', $tenantId)
                        ->where('status', 'offline')
                        ->count(),
                    'description' => 'Infrastructure nodes that need attention',
                    'href' => route('routers.index'),
                    'tone' => 'slate',
                ],
                [
                    'title' => 'IP pools near capacity',
                    'value' => $poolsNearCapacityCount,
                    'description' => 'Pools above 80% utilization or exhausted',
                    'href' => route('ipam.pools.index'),
                    'tone' => 'sky',
                ],
                [
                    'title' => 'Customers over limit',
                    'value' => Customer::query()
                        ->where('tenant_id', $tenantId)
                        ->whereColumn('balance', '>', 'credit_limit')
                        ->count(),
                    'description' => 'Accounts above configured credit limit',
                    'href' => route('customers.index'),
                    'tone' => 'violet',
                ],
            ],
            'service_breakdown' => [
                'subscriptions' => [
                    'total' => Subscription::query()
                        ->where('tenant_id', $tenantId)
                        ->count(),
                    'items' => $this->statusBreakdown($tenantId),
                ],
                'connections' => $this->connectionBreakdown($tenantId),
            ],
            'popular_plans' => $this->popularPlans($tenantId, $tenant->currency ?? 'USD'),
            'recent_activity' => $this->recentActivity($tenantId),
            'recent_customers' => Customer::query()
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (Customer $customer): array {
                    return [
                        'name' => $customer->full_name,
                        'status' => $customer->status,
                        'created_at' => $customer->created_at?->diffForHumans(),
                        'href' => route('customers.show', $customer),
                    ];
                }),
            'recent_subscriptions' => Subscription::query()
                ->where('tenant_id', $tenantId)
                ->with(['customer:id,name,first_name,last_name,company_name,customer_type', 'plan:id,name', 'router:id,name'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (Subscription $subscription) use ($tenant): array {
                    return [
                        'code' => $subscription->subscription_code,
                        'customer' => $subscription->customer?->full_name ?? 'Unknown customer',
                        'plan' => $subscription->plan?->name ?? 'Unassigned plan',
                        'router' => $subscription->router?->name ?? 'No router',
                        'status' => $subscription->status,
                        'price' => $this->formatMoney((float) $subscription->total_price, $tenant->currency ?? 'USD'),
                        'created_at' => $subscription->created_at?->diffForHumans(),
                        'href' => route('subscriptions.show', $subscription),
                    ];
                }),
            'router_health' => Router::query()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('status')
                ->orderByDesc('active_sessions_count')
                ->limit(5)
                ->get()
                ->map(function (Router $router): array {
                    return [
                        'name' => $router->name,
                        'site' => $router->site ?: ($router->location ?: 'No site'),
                        'status' => $router->status,
                        'sessions' => (int) $router->active_sessions_count,
                        'cpu' => (int) $router->cpu_usage,
                        'memory' => (int) $router->memory_usage,
                        'uptime' => $router->uptime ?: 'Unavailable',
                        'href' => route('routers.show', $router),
                    ];
                }),
            'ip_pools' => IpPool::query()
                ->where('tenant_id', $tenantId)
                ->with('router:id,name')
                ->orderByRaw('(used_ips / NULLIF(total_ips, 0)) desc')
                ->limit(5)
                ->get()
                ->map(function (IpPool $pool): array {
                    return [
                        'name' => $pool->name,
                        'router' => $pool->router?->name ?? 'No router',
                        'cidr' => $pool->cidr_notation,
                        'status' => $pool->status,
                        'used' => (int) $pool->used_ips,
                        'available' => (int) $pool->available_ips,
                        'reserved' => (int) $pool->reserved_ips,
                        'total' => (int) $pool->total_ips,
                        'usage_percentage' => $pool->usage_percentage,
                    ];
                }),
            'empty_state' => [
                'show' => $customerCount === 0 && $activeSubscriptionsCount === 0 && $routerTotals['total'] === 0,
                'actions' => [
                    [
                        'label' => 'Add customer',
                        'href' => route('customers.create'),
                    ],
                    [
                        'label' => 'Create subscription',
                        'href' => route('subscriptions.create'),
                    ],
                    [
                        'label' => 'Add router',
                        'href' => route('routers.create'),
                    ],
                ],
            ],
        ];

        return view('dashboard', compact('dashboard'));
    }

    private function resolveTenant(): Tenant
    {
        $tenant = tenant();

        if (! $tenant && auth()->check() && auth()->user()->tenant_id) {
            $tenant = Tenant::query()->find(auth()->user()->tenant_id);
        }

        return $tenant ?? throw new \RuntimeException('Tenant not found.');
    }

    private function growthMeta(Builder $query, string $resourceLabel): string
    {
        $currentStart = now()->startOfMonth();
        $previousStart = (clone $currentStart)->subMonth();
        $previousEnd = (clone $currentStart)->subSecond();

        $currentCount = (clone $query)
            ->where('created_at', '>=', $currentStart)
            ->count();

        $previousCount = (clone $query)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        if ($currentCount === 0 && $previousCount === 0) {
            return 'No new '.$resourceLabel.'s this month';
        }

        if ($previousCount === 0) {
            return $currentCount.' new this month';
        }

        $delta = $currentCount - $previousCount;
        $direction = $delta >= 0 ? 'up' : 'down';

        return sprintf(
            '%s %s vs last month (%d this month)',
            abs($delta),
            $direction,
            $currentCount,
        );
    }

    private function statusBreakdown(string $tenantId): Collection
    {
        $total = max(
            1,
            Subscription::query()
                ->where('tenant_id', $tenantId)
                ->count(),
        );

        return collect([
            ['status' => 'active', 'label' => 'Active', 'color' => 'emerald'],
            ['status' => 'pending', 'label' => 'Pending', 'color' => 'amber'],
            ['status' => 'suspended', 'label' => 'Suspended', 'color' => 'rose'],
            ['status' => 'cancelled', 'label' => 'Cancelled', 'color' => 'slate'],
        ])->map(function (array $item) use ($tenantId, $total): array {
            $count = Subscription::query()
                ->where('tenant_id', $tenantId)
                ->where('status', $item['status'])
                ->count();

            $item['count'] = $count;
            $item['percentage'] = (int) round(($count / $total) * 100);

            return $item;
        });
    }

    private function connectionBreakdown(string $tenantId): Collection
    {
        $total = max(
            1,
            Subscription::query()
                ->where('tenant_id', $tenantId)
                ->count(),
        );

        return collect([
            ['type' => 'pppoe', 'label' => 'PPPoE', 'color' => 'sky'],
            ['type' => 'dhcp', 'label' => 'DHCP', 'color' => 'violet'],
            ['type' => 'static', 'label' => 'Static', 'color' => 'emerald'],
        ])->map(function (array $item) use ($tenantId, $total): array {
            $count = Subscription::query()
                ->where('tenant_id', $tenantId)
                ->where('connection_type', $item['type'])
                ->count();

            $item['count'] = $count;
            $item['percentage'] = (int) round(($count / $total) * 100);

            return $item;
        });
    }

    private function popularPlans(string $tenantId, string $currency): Collection
    {
        return Subscription::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereNotNull('plan_id')
            ->with('plan:id,name,download_speed,upload_speed,bandwidth_unit')
            ->selectRaw('plan_id, COUNT(*) as subscriptions_count, SUM(total_price) as recurring_value')
            ->groupBy('plan_id')
            ->orderByDesc('subscriptions_count')
            ->limit(5)
            ->get()
            ->map(function (Subscription $subscription) use ($currency): array {
                return [
                    'name' => $subscription->plan?->name ?? 'Unknown plan',
                    'speed' => $subscription->plan
                        ? $subscription->plan->download_speed.' / '.$subscription->plan->upload_speed.' '.$subscription->plan->bandwidth_unit
                        : 'No speed profile',
                    'subscribers' => (int) $subscription->subscriptions_count,
                    'recurring_value' => $this->formatMoney((float) $subscription->recurring_value, $currency),
                ];
            });
    }

    private function recentActivity(string $tenantId): Collection
    {
        $activity = ActivityLog::query()
            ->where('tenant_id', $tenantId)
            ->with('user:id,name')
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (ActivityLog $log): array {
                return [
                    'title' => Str::headline(str_replace('.', ' ', $log->action)),
                    'description' => $log->user?->name
                        ? 'By '.$log->user->name
                        : 'System event',
                    'time' => $log->created_at?->diffForHumans(),
                    'tone' => $this->activityTone($log->action),
                ];
            });

        if ($activity->isNotEmpty()) {
            return $activity;
        }

        return $this->fallbackActivity($tenantId)->take(6)->values();
    }

    private function fallbackActivity(string $tenantId): Collection
    {
        $customers = Customer::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Customer $customer): array {
                return [
                    'title' => 'Customer added',
                    'description' => $customer->full_name,
                    'time' => $customer->created_at?->diffForHumans(),
                    'tone' => 'emerald',
                    'sort_at' => $customer->created_at?->timestamp ?? 0,
                ];
            });

        $subscriptions = Subscription::query()
            ->where('tenant_id', $tenantId)
            ->with('customer:id,name,first_name,last_name,company_name,customer_type')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Subscription $subscription): array {
                return [
                    'title' => 'Subscription created',
                    'description' => $subscription->customer?->full_name ?? $subscription->subscription_code,
                    'time' => $subscription->created_at?->diffForHumans(),
                    'tone' => 'sky',
                    'sort_at' => $subscription->created_at?->timestamp ?? 0,
                ];
            });

        $routers = Router::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(2)
            ->get()
            ->map(function (Router $router): array {
                return [
                    'title' => 'Router added',
                    'description' => $router->name,
                    'time' => $router->created_at?->diffForHumans(),
                    'tone' => 'violet',
                    'sort_at' => $router->created_at?->timestamp ?? 0,
                ];
            });

        return $customers
            ->concat($subscriptions)
            ->concat($routers)
            ->sortByDesc(fn (array $item): int => $item['sort_at'])
            ->map(function (array $item): array {
                unset($item['sort_at']);

                return $item;
            });
    }

    private function activityTone(string $action): string
    {
        return match (true) {
            Str::contains($action, ['delete', 'suspend', 'cancel']) => 'rose',
            Str::contains($action, ['create', 'activate']) => 'emerald',
            Str::contains($action, ['update']) => 'sky',
            default => 'slate',
        };
    }

    private function formatMoney(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => 'EUR ',
            'GBP' => 'GBP ',
            'NGN' => 'NGN ',
            'KES' => 'KES ',
            'GHS' => 'GHS ',
            'UGX' => 'UGX ',
            'TZS' => 'TZS ',
        ];

        $prefix = $symbols[$currency] ?? $currency.' ';

        return $prefix.number_format($amount, 2);
    }
}
