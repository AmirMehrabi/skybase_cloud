@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $toneClasses = [
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ];

    $statusClasses = [
        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'suspended' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'online' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'offline' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'exhausted' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'disabled' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-900 text-white shadow-sm">
        <div class="grid gap-8 px-6 py-7 md:px-8 lg:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)] lg:items-end">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-100 ring-1 ring-inset ring-white/15">
                        Tenant dashboard
                    </span>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $toneClasses[$dashboard['tenant']['status'] === 'active' ? 'emerald' : 'amber'] ?? $toneClasses['emerald'] }}">
                        {{ ucfirst($dashboard['tenant']['status']) }}
                    </span>
                    @if ($dashboard['tenant']['is_on_trial'])
                        <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-xs font-medium text-amber-100 ring-1 ring-inset ring-amber-300/30">
                            Trial ends {{ $dashboard['tenant']['trial_ends_at'] }}
                        </span>
                    @endif
                </div>

                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-medium text-sky-100/80">Welcome back</p>
                        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ $dashboard['tenant']['name'] }}</h1>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">
                        A live operations view of your subscribers, service health, routers, and IP capacity.
                        Every metric on this page is scoped to your current tenant.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('customers.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-sky-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add customer
                    </a>
                    <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-sky-400/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-sky-200/20 transition hover:bg-sky-400/25">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1 1 0 0113.293 3.293l4.414 4.414A1 1 0 0118 8.414V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Create subscription
                    </a>
                    <a href="{{ route('routers.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        Add router
                    </a>
                </div>
            </div>

            <div class="grid gap-3 rounded-[24px] border border-white/10 bg-white/5 p-4 backdrop-blur-sm sm:grid-cols-2">
                @foreach ($dashboard['stats'] as $stat)
                    <a href="{{ $stat['href'] }}" class="rounded-2xl border border-white/10 bg-black/10 p-4 transition hover:bg-black/20">
                        <p class="text-xs font-medium uppercase tracking-[0.22em] text-slate-300">{{ $stat['label'] }}</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-white">{{ $stat['value'] }}</p>
                        <p class="mt-3 text-sm leading-5 text-slate-300">{{ $stat['meta'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($dashboard['empty_state']['show'])
        <section class="rounded-[24px] border border-dashed border-slate-300 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl space-y-2">
                    <h2 class="text-xl font-semibold text-slate-900">Your tenant is ready for setup</h2>
                    <p class="text-sm leading-6 text-slate-600">
                        Start with the essentials: create your first customer, connect a router, and provision the first subscription.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @foreach ($dashboard['empty_state']['actions'] as $action)
                        <a href="{{ $action['href'] }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($dashboard['attention'] as $item)
            <a href="{{ $item['href'] }}" class="rounded-[22px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $item['title'] }}</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $item['value'] }}</p>
                    </div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $toneClasses[$item['tone']] ?? $toneClasses['slate'] }}">
                        Focus
                    </span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>
            </a>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
        <div class="space-y-6">
            <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Service mix</h2>
                        <p class="text-sm text-slate-500">Current tenant-wide subscription and access-method breakdown.</p>
                    </div>
                    <a href="{{ route('subscriptions.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">View subscriptions</a>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-800">Subscription statuses</h3>
                            <span class="text-xs font-medium text-slate-500">{{ $dashboard['service_breakdown']['subscriptions']['total'] }} total</span>
                        </div>
                        <div class="mt-4 space-y-4">
                            @foreach ($dashboard['service_breakdown']['subscriptions']['items'] as $item)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full {{ $item['color'] === 'emerald' ? 'bg-emerald-500' : ($item['color'] === 'amber' ? 'bg-amber-500' : ($item['color'] === 'rose' ? 'bg-rose-500' : 'bg-slate-500')) }}"></span>
                                            <span class="font-medium text-slate-700">{{ $item['label'] }}</span>
                                        </div>
                                        <span class="text-slate-500">{{ $item['count'] }} • {{ $item['percentage'] }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full {{ $item['color'] === 'emerald' ? 'bg-emerald-500' : ($item['color'] === 'amber' ? 'bg-amber-500' : ($item['color'] === 'rose' ? 'bg-rose-500' : 'bg-slate-500')) }}" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-800">Connection types</h3>
                            <span class="text-xs font-medium text-slate-500">Provisioning footprint</span>
                        </div>
                        <div class="mt-4 space-y-4">
                            @foreach ($dashboard['service_breakdown']['connections'] as $item)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $item['count'] }} subscriptions</p>
                                        </div>
                                        <p class="text-xl font-semibold text-slate-900">{{ $item['percentage'] }}%</p>
                                    </div>
                                    <div class="mt-3 h-2 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full {{ $item['color'] === 'sky' ? 'bg-sky-500' : ($item['color'] === 'violet' ? 'bg-violet-500' : 'bg-emerald-500') }}" style="width: {{ $item['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Popular plans</h2>
                            <p class="text-sm text-slate-500">Top plans by active subscriptions in this tenant.</p>
                        </div>
                        <a href="{{ route('plans.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">Manage plans</a>
                    </div>
                    <div class="mt-6 space-y-3">
                        @forelse ($dashboard['popular_plans'] as $plan)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $plan['name'] }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $plan['speed'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900">{{ $plan['subscribers'] }} active</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $plan['recurring_value'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                No active plan distribution yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Recent activity</h2>
                            <p class="text-sm text-slate-500">Latest tenant-scoped operational activity and setup events.</p>
                        </div>
                        <a href="{{ route('admin.tenant.users.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">Manage users</a>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($dashboard['recent_activity'] as $activity)
                            <div class="flex gap-3">
                                <div class="mt-1 h-10 w-10 flex-none rounded-2xl ring-1 ring-inset {{ $toneClasses[$activity['tone']] ?? $toneClasses['slate'] }}"></div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">{{ $activity['title'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $activity['description'] }}</p>
                                    <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] text-slate-400">{{ $activity['time'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                No recent activity logged for this tenant yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Router health</h2>
                        <p class="text-sm text-slate-500">Live device posture based on saved router telemetry.</p>
                    </div>
                    <a href="{{ route('routers.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">View routers</a>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($dashboard['router_health'] as $router)
                        <a href="{{ $router['href'] }}" class="block rounded-2xl border border-slate-200 p-4 transition hover:border-sky-300 hover:bg-sky-50/40">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">{{ $router['name'] }}</p>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses[$router['status']] ?? $statusClasses['offline'] }}">
                                            {{ ucfirst($router['status']) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $router['site'] }}</p>
                                </div>
                                <p class="text-sm font-medium text-slate-500">{{ $router['sessions'] }} sessions</p>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-slate-500">CPU</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $router['cpu'] }}%</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-slate-500">Memory</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $router['memory'] }}%</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-slate-500">Uptime</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $router['uptime'] }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No routers configured yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">IP pool utilization</h2>
                        <p class="text-sm text-slate-500">Top pools by current consumption.</p>
                    </div>
                    <a href="{{ route('ipam.pools.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">Open IPAM</a>
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($dashboard['ip_pools'] as $pool)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-900">{{ $pool['name'] }}</p>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses[$pool['status']] ?? $statusClasses['disabled'] }}">
                                            {{ ucfirst($pool['status']) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $pool['cidr'] }} • {{ $pool['router'] }}</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ $pool['usage_percentage'] }}%</p>
                            </div>
                            <div class="mt-4 h-2 rounded-full bg-slate-200">
                                <div class="h-2 rounded-full {{ $pool['usage_percentage'] >= 90 ? 'bg-rose-500' : ($pool['usage_percentage'] >= 80 ? 'bg-amber-500' : 'bg-sky-500') }}" style="width: {{ $pool['usage_percentage'] }}%"></div>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-3 text-xs font-medium text-slate-500">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p>Used</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $pool['used'] }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p>Available</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $pool['available'] }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p>Reserved</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $pool['reserved'] }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No IP pools found for this tenant.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Newest customers</h2>
                    <p class="text-sm text-slate-500">Recently created accounts in this tenant.</p>
                </div>
                <a href="{{ route('customers.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">Open customers</a>
            </div>
            <div class="mt-6 space-y-3">
                @forelse ($dashboard['recent_customers'] as $customer)
                    <a href="{{ $customer['href'] }}" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50/40">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $customer['name'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $customer['created_at'] }}</p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses[$customer['status']] ?? $statusClasses['offline'] }}">
                            {{ ucfirst($customer['status']) }}
                        </span>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                        No customers added yet.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Newest subscriptions</h2>
                    <p class="text-sm text-slate-500">Recently provisioned services with plan and router context.</p>
                </div>
                <a href="{{ route('subscriptions.index') }}" class="text-sm font-semibold text-sky-700 hover:text-sky-800">Open subscriptions</a>
            </div>
            <div class="mt-6 space-y-3">
                @forelse ($dashboard['recent_subscriptions'] as $subscription)
                    <a href="{{ $subscription['href'] }}" class="block rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $subscription['code'] }}</p>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses[$subscription['status']] ?? $statusClasses['offline'] }}">
                                        {{ ucfirst($subscription['status']) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ $subscription['customer'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ $subscription['plan'] }} • {{ $subscription['router'] }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="font-semibold text-slate-900">{{ $subscription['price'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $subscription['created_at'] }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                        No subscriptions created yet.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
