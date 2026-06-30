@extends('layouts.admin')

@section('title', 'Subscription Details')

@php
    $subscriptionModel = $subscription;
    $currentIpAddress = old('ip_address', $subscriptionModel->ip_address ?? '');
    $canSuggestIp = $subscriptionModel->isSystemManagedIp() && $subscriptionModel->ipPool;
    $plans = collect($plans ?? [])->values();
    $subscription = [
        'id' => $subscription->id,
        'subscription_code' => $subscription->subscription_code,
        'name' => $subscription->name ?: $subscription->customer?->full_name ?? 'N/A',
        'service_type' => $subscription->service_type ?? 'hotspot',
        'customer' => [
            'id' => $subscription->customer?->id,
            'name' => $subscription->customer?->full_name ?? 'N/A',
            'email' => $subscription->customer?->email ?? 'N/A',
            'phone' => $subscription->customer?->phone ?? ($subscription->customer?->mobile ?? 'N/A'),
        ],
        'plan_name' => $subscription->plan?->name ?? 'N/A',
        'plan_price' => (float) ($subscription->plan?->price ?? 0),
        'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
        'status' => $subscription->status,
        'site' => $subscription->site ?? ($subscription->customer?->site ?? 'N/A'),
        'router' => $subscription->router?->name ?? 'N/A',
        'ip_address' => $subscription->ip_address ?? 'N/A',
        'mac_address' => $subscription->mac_address ?? 'N/A',
        'pppoe_username' => $subscription->pppoe_username ?? 'N/A',
        'pppoe_password' => $subscription->pppoe_password ?? 'N/A',
        'start_date' => optional(
            $subscription->start_date ?? ($subscription->activation_date ?? $subscription->created_at),
        )->toDateString(),
        'end_date' => optional(
            $subscription->end_date ?? $subscription->created_at?->copy()->addYear(),
        )->toDateString(),
        'billing_enabled' => (bool) $subscription->billing_enabled,
        'grace_period_days' => $subscription->effectiveGracePeriodDays(),
        'next_billing_date' => optional(
            $subscription->next_billing_date ?? $subscription->created_at?->copy()->addMonth(),
        )->toDateString(),
        'last_billing_date' => optional($subscription->last_billed_at ?? $subscription->created_at)->toDateString(),
        'auto_renew' => (bool) $subscription->billing_enabled,
        'contract_start' => optional(
            $subscription->start_date ?? ($subscription->activation_date ?? $subscription->created_at),
        )->toDateString(),
        'contract_end' => optional(
            $subscription->end_date ?? $subscription->created_at?->copy()->addYear(),
        )->toDateString(),
        'discount_percentage' =>
            filled($subscription->base_price) && (float) $subscription->base_price > 0
                ? (int) round(((float) $subscription->discount_amount / (float) $subscription->base_price) * 100)
                : 0,
        'tax_percentage' =>
            filled($subscription->base_price) && (float) $subscription->base_price > 0
                ? (int) round(((float) $subscription->tax_amount / (float) $subscription->base_price) * 100)
                : 0,
        'monthly_price' => (float) ($subscription->plan?->price ?? ($subscription->total_price ?? 0)),
        'total_price' => (float) $subscription->total_price,
        'balance' => (float) ($subscription->customer?->balance ?? 0),
        'data_quota' => (float) ($usageSummary['quota_gb'] ?? 0),
        'data_used' => (float) ($usageSummary['total_gb'] ?? 0),
        'created_at' => optional($subscription->created_at)->toDateString(),
        'speed_download' => (int) ($subscription->plan?->download_speed ?? 0),
        'speed_upload' => (int) ($subscription->plan?->upload_speed ?? 0),
        'burst_mode' => filled($subscription->plan?->burst_download) || filled($subscription->plan?->burst_upload),
    ];

    $usagePercent = $usageSummary['usage_percent'] ?? 0;
    $quotaLabel = $usageSummary['quota_label'] ?? 'Unlimited';
    $billingInvoices = collect($billingInvoices ?? []);
    $usageSessions = collect($usageSessions ?? []);

    if (!function_exists('getStatusBadgeClass')) {
        function getStatusBadgeClass($status)
        {
            $classes = [
                'active' => 'bg-green-100 text-green-800 border-green-200',
                'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'suspended' => 'bg-red-100 text-red-800 border-red-200',
                'cancelled' => 'bg-gray-100 text-gray-800 border-gray-200',
                'expired' => 'bg-gray-100 text-gray-800 border-gray-200',
                'paid' => 'bg-green-100 text-green-800 border-green-200',
            ];

            return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
        }
    }
@endphp

@section('content')
    <div x-data="{
        tab: @js(request('tab', 'overview')),
        copiedField: null,
        credentials: {
            open: {{ $errors->has('pppoe_username') || $errors->has('pppoe_password') ? 'true' : 'false' }},
            form: {
                pppoe_username: @js(old('pppoe_username', $subscriptionModel->pppoe_username ?? '')),
                pppoe_password: @js(old('pppoe_password', $subscriptionModel->pppoe_password ?? '')),
            },
            submitting: false,
            message: null,
            error: null,
        },
        ipChange: {
            open: {{ $errors->has('ip_address') ? 'true' : 'false' }},
            form: {
                ip_address: @js($currentIpAddress),
            },
            suggestedIp: @js($currentIpAddress),
            submitting: false,
            suggesting: false,
            message: null,
            error: null,
        },
        planChange: {
            open: {{ $errors->has('plan_id') || $errors->has('billing_cycle') || $errors->has('grace_period_days') || $errors->has('next_billing_date') ? 'true' : 'false' }},
            submitting: false,
            plans: @js($plans),
            form: {
                plan_id: @js(old('plan_id', (string) $subscriptionModel->plan_id)),
                billing_cycle: @js(old('billing_cycle', $subscriptionModel->billing_cycle)),
                grace_period_days: @js(old('grace_period_days', $subscriptionModel->grace_period_days)),
                billing_enabled: @js(old('billing_enabled', $subscriptionModel->billing_enabled ? '1' : '0')),
                next_billing_date: @js(old('next_billing_date', optional($subscriptionModel->next_billing_date)->toDateString())),
            },
            get selectedPlan() {
                return this.plans.find((plan) => String(plan.id) === String(this.form.plan_id)) || null;
            },
            syncSelectedPlan() {
                const plan = this.selectedPlan;
    
                if (!plan) {
                    return;
                }
    
                this.form.billing_cycle = plan.billing_cycle || this.form.billing_cycle || 'monthly';
                this.form.grace_period_days = plan.grace_period_days ?? this.form.grace_period_days ?? 7;
            },
        },
        bandwidth: {
            live: { rx_bps: 0, tx_bps: 0, interface_name: null, source: 'routeros', sampled_at: null, status: 'available', rrd_available: false, last_sampled_at: null, stale: true },
            history: [],
            range: '1h',
            timer: null,
            loading: false,
            historyLoading: false,
            hasData: false,
            tooltip: { show: false, x: 0, y: 0, rx: null, tx: null, time: '' },
        },
        initBandwidth() {
            this.loadBandwidthHistory();
            this.refreshLiveBandwidth();
            this.bandwidth.timer = setInterval(() => this.refreshLiveBandwidth(), 5000);
            setInterval(() => this.loadBandwidthHistory(false), 60000);
        },
        async copyCredential(field, value) {
            if (!value || value === 'N/A') {
                return;
            }
    
            try {
                await navigator.clipboard.writeText(value);
                this.copiedField = field;
                setTimeout(() => {
                    if (this.copiedField === field) {
                        this.copiedField = null;
                    }
                }, 1200);
            } catch (error) {
                this.copiedField = null;
            }
        },
        openCredentialsModal() {
            this.credentials.open = true;
            this.credentials.error = null;
            this.credentials.message = null;
        },
        closeCredentialsModal() {
            this.credentials.open = false;
            this.credentials.error = null;
            this.credentials.message = null;
        },
        openIpModal() {
            this.ipChange.open = true;
            this.ipChange.error = null;
            this.ipChange.message = null;
            this.ipChange.form.ip_address = this.ipChange.form.ip_address || this.ipChange.suggestedIp || '';
        },
        closeIpModal() {
            this.ipChange.open = false;
            this.ipChange.error = null;
        },
        openPlanModal() {
            this.planChange.syncSelectedPlan();
            this.planChange.open = true;
        },
        closePlanModal() {
            this.planChange.open = false;
        },
        async suggestIpAddress() {
            if (!@js((bool) $canSuggestIp)) {
                this.ipChange.error = 'IP suggestions are only available for system-managed pools.';
                return;
            }
    
            this.ipChange.suggesting = true;
            this.ipChange.error = null;
            this.ipChange.message = 'Looking up a free IP address...';
    
            try {
                const response = await fetch(@js(route('subscriptions.suggest-ip', $subscriptionModel)), {
                    headers: { 'Accept': 'application/json' },
                });
                const payload = await response.json();
    
                if (!response.ok) {
                    this.ipChange.error = payload.message || 'No free IP address is available right now.';
                    this.ipChange.message = null;
                    return;
                }
    
                this.ipChange.suggestedIp = payload.ip_address;
                this.ipChange.form.ip_address = payload.ip_address;
                this.ipChange.message = `Suggested ${payload.ip_address} from ${payload.pool_name}.`;
                this.ipChange.error = null;
            } catch (error) {
                this.ipChange.error = 'Unable to fetch a suggested IP address.';
                this.ipChange.message = null;
            } finally {
                this.ipChange.suggesting = false;
            }
        },
        async refreshLiveBandwidth() {
            this.bandwidth.loading = true;

            try {
                const response = await fetch(@js(route('subscriptions.bandwidth.live', $subscription['id'])), {
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    this.bandwidth.live = await response.json();
                }
            } finally {
                this.bandwidth.loading = false;
            }
        },
        async loadBandwidthHistory() {
            this.bandwidth.historyLoading = true;

            try {
                const params = new URLSearchParams({ range: this.bandwidth.range });
                const response = await fetch(@js(route('subscriptions.bandwidth.history', $subscription['id'])) + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                this.bandwidth.history = payload.chartData || [];
                this.bandwidth.hasData = Boolean(payload.hasData);
                this.bandwidth.tooltip.show = false;
            } catch (error) {
                // Retain the last successful series. The chart itself remains usable.
            } finally {
                this.bandwidth.historyLoading = false;
            }
        },
        chartMax() {
            return Math.max(1, ...this.bandwidth.history.flatMap((point) => [
                Number(point.rx_bps || 0),
                Number(point.tx_bps || 0),
            ]));
        },
        chartSegments(field) {
            const points = this.bandwidth.history;
            const max = this.chartMax();
            const last = Math.max(1, points.length - 1);
            const segments = [];
            let current = [];

            points.forEach((point, index) => {
                const value = point[field];

                if (value === null || value === undefined) {
                    if (current.length) {
                        segments.push(current);
                        current = [];
                    }

                    return;
                }

                current.push({
                    x: 56 + ((index / last) * 920),
                    y: 264 - ((Number(value) / max) * 232),
                });
            });

            if (current.length) {
                segments.push(current);
            }

            return segments;
        },
        linePath(segment) {
            return segment.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ');
        },
        areaPath(segment) {
            if (!segment.length) {
                return '';
            }

            return `${this.linePath(segment)} L ${segment[segment.length - 1].x} 264 L ${segment[0].x} 264 Z`;
        },
        axisLabels() {
            const points = this.bandwidth.history;

            if (!points.length) {
                return [];
            }

            return [...new Set([0, Math.floor((points.length - 1) / 2), points.length - 1])]
                .map((index) => ({ x: 56 + ((index / Math.max(1, points.length - 1)) * 920), label: points[index].time }));
        },
        updateBandwidthTooltip(event) {
            if (!this.bandwidth.history.length) {
                return;
            }

            const bounds = event.currentTarget.getBoundingClientRect();
            const ratio = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
            const index = Math.round(ratio * (this.bandwidth.history.length - 1));
            const point = this.bandwidth.history[index];
            this.bandwidth.tooltip = {
                show: true,
                x: Math.max(72, Math.min(928, 56 + (ratio * 920))),
                y: 26,
                rx: point.rx_bps,
                tx: point.tx_bps,
                time: point.time,
            };
        },
        formatSpeed(bits) {
            const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
            let size = Number(bits || 0);
            let unit = 0;

            while (size >= 1000 && unit < units.length - 1) {
                size = size / 1000;
                unit++;
            }

            return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
        },
    }" x-init="initBandwidth()" class="space-y-6">
        <!-- Top Header Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <!-- Subscription Info -->
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900">{{ $subscription['name'] }}</h1>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border {{ getStatusBadgeClass($subscription['status']) }}">
                                {{ ucfirst($subscription['status']) }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                            <span>{{ $subscription['subscription_code'] }}</span>
                            <span>•</span>
                            <span>{{ $subscription['customer']['name'] }}</span>
                            <span>&bull;</span>
                            <span>{{ $subscription['plan_name'] }}</span>
                            <span>&bull;</span>
                            <span>${{ number_format($subscription['monthly_price'], 2) }}/mo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                <a href="{{ route('subscriptions.edit', $subscription['id']) }}"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Edit
                </a>

                <button type="button" @click="openPlanModal()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5h6m-6 14h6M7 5h10l1 7-1 7H7L6 12l1-7z"></path>
                    </svg>
                    Change Plan
                </button>

                <button type="button" @click="openCredentialsModal()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-700 transition hover:bg-teal-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 7a3 3 0 10-6 0v3H7a2 2 0 00-2 2v5a2 2 0 002 2h8a2 2 0 002-2v-5a2 2 0 00-2-2h-2V7zm-3 7v2">
                        </path>
                    </svg>
                    Manage Credentials
                </button>

                @if ($subscription['status'] === 'active')
                    <form method="POST" action="{{ route('subscriptions.suspend', $subscription['id']) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm font-medium text-yellow-700 transition hover:bg-yellow-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Suspend
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('subscriptions.activate', $subscription['id']) }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 transition hover:bg-green-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            Activate
                        </button>
                    </form>
                @endif

                @if ($subscriptionModel->isPppoe() && filled($subscriptionModel->pppoe_username) && $subscriptionModel->router)
                    <form method="POST" action="{{ route('subscriptions.kill-session', $subscription['id']) }}">
                        @csrf
                        <button type="submit"
                            onclick="return confirm('Disconnect the active PPP session for this subscription?')"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 transition hover:bg-red-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 5.636L5.636 18.364M5.636 5.636l12.728 12.728"></path>
                            </svg>
                            Kill
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('subscriptions.generate-invoice', $subscription['id']) }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 text-sm font-medium text-purple-700 transition hover:bg-purple-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Create Invoice
                    </button>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Balance</p>
                <p class="mt-1 text-xl font-bold text-gray-900">${{ number_format($subscription['balance'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Next Billing</p>
                <p class="mt-1 text-xl font-bold text-gray-900">
                    {{ \Carbon\Carbon::parse($subscription['next_billing_date'])->format('M d, Y') }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Data Used</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($subscription['data_used']) }} GB</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-gray-500">Contract Ends</p>
                <p class="mt-1 text-xl font-bold text-gray-900">
                    {{ \Carbon\Carbon::parse($subscription['contract_end'])->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="mt-6 mb-6 rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-50 to-white p-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            {{ $subscriptionModel->isSystemManagedIp() ? 'IP Pool Assignment' : 'IP Assignment' }}</h2>
                        @if ($subscriptionModel->isSystemManagedIp() && $subscriptionModel->ipPool)
                            <span
                                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                {{ $subscriptionModel->ipPool->name }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-700">
                        <span
                            class="rounded-lg bg-white px-3 py-2 font-mono text-gray-900 ring-1 ring-inset ring-gray-200">
                            {{ $subscription['ip_address'] }}
                        </span>
                        <span>{{ $subscriptionModel->isSystemManagedIp() ? 'System managed' : 'Router managed' }}</span>
                        @if ($subscriptionModel->ipPool)
                            <span>•</span>
                            <span>{{ $subscriptionModel->ipPool->available_ips }} free</span>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="openIpModal()"
                        class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Change IP
                    </button>
                    <a href="{{ route('subscriptions.edit', $subscription['id']) }}#ip_address"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Open Edit Form
                    </a>
                </div>
            </div>
            @if ($subscriptionModel->ipRoutes->isNotEmpty())
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">IP Routes</h3>
                        <form method="POST" action="{{ route('subscriptions.ip-routes.sync', $subscriptionModel) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-50">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v6h6M20 20v-6h-6M20 9a8 8 0 0 0-14.32-3.91L4 10m16 4-1.68 4.91A8 8 0 0 1 4 15">
                                    </path>
                                </svg>
                                Sync RADIUS
                            </button>
                        </form>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach ($subscriptionModel->ipRoutes as $ipRoute)
                            <div class="rounded-xl border border-gray-200 bg-white p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span
                                        class="font-mono text-sm font-semibold text-gray-900">{{ $ipRoute->destinationAddress() }}</span>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $ipRoute->routeros_sync_status === 'synced' ? 'border-green-200 bg-green-50 text-green-700' : ($ipRoute->routeros_sync_status === 'failed' ? 'border-red-200 bg-red-50 text-red-700' : 'border-yellow-200 bg-yellow-50 text-yellow-700') }}">
                                        {{ ucfirst($ipRoute->routeros_sync_status) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    {{ $ipRoute->ipPool?->name ?? 'IPAM removed' }} via {{ $subscription['ip_address'] }}
                                </div>
                                @if ($ipRoute->routeros_sync_error)
                                    <div class="mt-2 text-xs text-red-600">{{ $ipRoute->routeros_sync_error }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Billing Controls -->
        <div class="bg-white rounded-2xl p-6 mb-6 border border-gray-200 shadow-sm">
            <form method="POST" action="{{ route('subscriptions.billing.update', $subscription['id']) }}"
                class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_160px_180px_auto] gap-4 md:items-end">
                @csrf
                @method('PATCH')
                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                    <div>
                        <label for="subscription_billing_enabled" class="text-sm font-medium text-gray-700">Billing
                            Enabled</label>
                        <p class="text-xs text-gray-500 mt-1">Controls invoice generation and overdue suspension for this
                            subscription.</p>
                    </div>
                    <div>
                        <input type="hidden" name="billing_enabled" value="0">
                        <input type="checkbox" id="subscription_billing_enabled" name="billing_enabled" value="1"
                            @checked($subscription['billing_enabled'])
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label for="subscription_grace_period_days" class="block text-sm font-medium text-gray-700 mb-1">Grace
                        Days</label>
                    <input type="number" id="subscription_grace_period_days" name="grace_period_days" min="0"
                        max="365" value="{{ $subscription['grace_period_days'] }}"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
                <div>
                    <label for="subscription_next_billing_date" class="block text-sm font-medium text-gray-700 mb-1">Next
                        Billing</label>
                    <input type="date" id="subscription_next_billing_date" name="next_billing_date"
                        value="{{ $subscription['next_billing_date'] }}"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save Billing
                </button>
            </form>
        </div>

        <div x-show="planChange.open" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4 py-8">
            <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl"
                @click.outside="closePlanModal()">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Change Plan</h3>
                        <p class="text-sm text-gray-500">Update the plan, Radius attributes, and billing settings in one
                            save.</p>
                    </div>
                    <button type="button" @click="closePlanModal()"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('subscriptions.update', $subscription['id']) }}"
                    class="space-y-5 px-6 py-5" @submit="planChange.submitting = true">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $subscriptionModel->name }}">
                    <input type="hidden" name="service_type" value="{{ $subscriptionModel->service_type }}">
                    <input type="hidden" name="router_id" value="{{ $subscriptionModel->router_id }}">
                    <input type="hidden" name="site" value="{{ $subscriptionModel->site }}">

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <label for="plan_change_plan_id"
                                    class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                                <select id="plan_change_plan_id" name="plan_id" x-model="planChange.form.plan_id"
                                    @change="planChange.syncSelectedPlan()"
                                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan['id'] }}">{{ $plan['name'] }} -
                                            ${{ number_format((float) $plan['price'], 2) }}/{{ $plan['billing_cycle'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plan_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <h4 class="text-sm font-semibold text-gray-900">Selected plan</h4>
                                <div class="mt-3 space-y-2 text-sm text-gray-700">
                                    <template x-if="planChange.selectedPlan">
                                        <div>
                                            <div class="font-medium text-gray-900" x-text="planChange.selectedPlan.name">
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500"
                                                x-text="[
                                            planChange.selectedPlan.download_speed + ' ' + planChange.selectedPlan.bandwidth_unit + ' down',
                                            planChange.selectedPlan.upload_speed + ' ' + planChange.selectedPlan.bandwidth_unit + ' up',
                                            planChange.selectedPlan.router_profile ? 'Router profile: ' + planChange.selectedPlan.router_profile : null,
                                            planChange.selectedPlan.ip_pool ? 'IP pool: ' + planChange.selectedPlan.ip_pool : null,
                                        ].filter(Boolean).join(' • ')">
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="! planChange.selectedPlan">
                                        <div class="text-gray-500">Select a plan to see its billing and Radius defaults.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="plan_change_billing_cycle"
                                        class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                                    <select id="plan_change_billing_cycle" name="billing_cycle"
                                        x-model="planChange.form.billing_cycle"
                                        class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @foreach (['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('billing_cycle')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="plan_change_grace_period_days"
                                        class="block text-sm font-medium text-gray-700 mb-1">Grace Period</label>
                                    <input id="plan_change_grace_period_days" type="number" min="0"
                                        max="365" name="grace_period_days"
                                        x-model="planChange.form.grace_period_days"
                                        class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('grace_period_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="plan_change_next_billing_date"
                                        class="block text-sm font-medium text-gray-700 mb-1">Next Billing Date</label>
                                    <input id="plan_change_next_billing_date" type="date" name="next_billing_date"
                                        x-model="planChange.form.next_billing_date"
                                        class="block w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('next_billing_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                                    <div>
                                        <label for="plan_change_billing_enabled"
                                            class="text-sm font-medium text-gray-700">Billing Enabled</label>
                                        <p class="text-xs text-gray-500 mt-1">Keep this subscription included in billing
                                            runs.</p>
                                    </div>
                                    <div>
                                        <input type="hidden" name="billing_enabled" value="0">
                                        <input id="plan_change_billing_enabled" type="checkbox" name="billing_enabled"
                                            value="1" x-model="planChange.form.billing_enabled"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-4">
                        <button type="button" @click="closePlanModal()"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Save Plan Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                    <button @click="tab = 'overview'"
                        :class="tab === 'overview' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Overview
                    </button>
                    <button @click="tab = 'billing'"
                        :class="tab === 'billing' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Billing
                    </button>
                    <button @click="tab = 'usage'"
                        :class="tab === 'usage' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Usage
                    </button>
                    <button @click="tab = 'auth'"
                        :class="tab === 'auth' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Auth Attempts
                    </button>
                    <button @click="tab = 'contract'"
                        :class="tab === 'contract' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Contract
                    </button>
                    <button @click="tab = 'invoices'"
                        :class="tab === 'invoices' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Invoices
                    </button>
                    <button @click="tab = 'activity'"
                        :class="tab === 'activity' ? 'border-blue-500 text-blue-600' :
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        Activity Log
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- TAB: Overview -->
                <div x-show="tab === 'overview'" x-transition class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Subscription Info -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Subscription Information</h3>
                                <p class="text-sm text-gray-500 mt-1">Basic subscription details</p>
                            </div>
                            <dl class="space-y-4">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Subscription Code</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['subscription_code'] }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Name</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['name'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Subscription Type</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ ['hotspot' => 'Hotspot', 'pppoe' => 'PPPoE', 'vpn' => 'VPN'][$subscription['service_type']] ?? ucfirst($subscription['service_type']) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Status</dt>
                                    <dd><span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($subscription['status']) }}">{{ ucfirst($subscription['status']) }}</span>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Service Plan</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['plan_name'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Billing Cycle</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ ucfirst($subscription['billing_cycle']) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Service Start Date</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($subscription['start_date'])->format('M d, Y') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Auto Renew</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        @if ($subscription['auto_renew'])
                                            <span class="inline-flex items-center gap-1 text-green-600">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                                Enabled
                                            </span>
                                        @else
                                            <span class="text-gray-400">Disabled</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Network Assignment -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Network Assignment</h3>
                                <p class="text-sm text-gray-500 mt-1">Network configuration details</p>
                            </div>
                            <dl class="space-y-4">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Site/Location</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['site'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Router/Device</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['router'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">IP Address</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['ip_address'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">MAC Address</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['mac_address'] }}</dd>
                                </div>
                                <div class="pt-3 border-t border-gray-200 space-y-3">
                                    <dt class="text-sm text-gray-500">PPP Credentials</dt>
                                    <dd class="space-y-2">
                                        <button type="button"
                                            @click="copyCredential('username', @js($subscription['pppoe_username']))"
                                            class="w-full inline-flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left hover:bg-gray-100 transition-colors">
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Username</span>
                                            <span
                                                class="font-mono text-sm font-medium text-gray-900">{{ $subscription['pppoe_username'] }}</span>
                                        </button>
                                        <button type="button"
                                            @click="copyCredential('password', @js($subscription['pppoe_password']))"
                                            class="w-full inline-flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left hover:bg-gray-100 transition-colors">
                                            <span class="text-xs text-gray-500 uppercase tracking-wide">Password</span>
                                            <span
                                                class="font-mono text-sm font-medium text-gray-900">{{ $subscription['pppoe_password'] }}</span>
                                        </button>
                                        <p class="text-xs text-green-600" x-show="copiedField" x-transition>
                                            <span
                                                x-text="copiedField === 'username' ? 'Username copied' : 'Password copied'"></span>
                                        </p>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Billing Summary -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Billing Summary</h3>
                                <p class="text-sm text-gray-500 mt-1">Current billing information</p>
                            </div>
                            <dl class="space-y-4">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Monthly Price</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        ${{ number_format($subscription['monthly_price'], 2) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Discount</dt>
                                    <dd class="text-sm font-medium text-green-600">
                                        -{{ $subscription['discount_percentage'] }}%</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Tax</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $subscription['tax_percentage'] }}%
                                    </dd>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200">
                                    <dt class="text-sm font-medium text-gray-900">Total Monthly</dt>
                                    <dd class="text-lg font-bold text-gray-900">
                                        ${{ number_format($subscription['total_price'], 2) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Current Balance</dt>
                                    <dd
                                        class="text-sm font-bold {{ $subscription['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        ${{ number_format($subscription['balance'], 2) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Next Billing Date</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($subscription['next_billing_date'])->format('M d, Y') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Data Quota Summary -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Data Quota Summary</h3>
                                <p class="text-sm text-gray-500 mt-1">Current usage information</p>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="text-sm text-gray-600">{{ number_format($subscription['data_used'], 2) }}
                                            GB used</span>
                                        <span class="text-sm text-gray-500">of {{ $quotaLabel }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="{{ $usagePercent > 90 ? 'bg-red-500' : ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500') }} h-3 rounded-full transition-all duration-500"
                                            style="width: {{ $usagePercent }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        {{ $usageSummary['quota_gb'] > 0 ? number_format($usagePercent, 1) . '%' : 'Unlimited plan' }}
                                    </p>
                                </div>

                                <div class="pt-4 border-t border-gray-200 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Download Speed</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $subscription['speed_download'] }}
                                            Mbps</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Upload Speed</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $subscription['speed_upload'] }}
                                            Mbps</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Burst Mode</span>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $subscription['burst_mode'] ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: Billing -->
                <div x-show="tab === 'billing'" x-transition class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Billing Overview</h3>
                            <p class="text-sm text-gray-500 mt-1">Current billing status and recent invoices</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Invoice Number</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Amount</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Due Date</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Paid Date</th>
                                        <th
                                            class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($billingInvoices->take(5) as $invoice)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ $invoice['url'] }}"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-700">{{ $invoice['invoice_number'] }}</a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-medium text-gray-900">${{ number_format($invoice['amount'], 2) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm text-gray-900">{{ $invoice['due_date'] ? \Carbon\Carbon::parse($invoice['due_date'])->format('M d, Y') : '—' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($invoice['status']) }}">{{ ucfirst($invoice['status']) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm text-gray-900">{{ $invoice['paid_date'] ? \Carbon\Carbon::parse($invoice['paid_date'])->format('M d, Y') : '—' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <a href="{{ $invoice['url'] }}"
                                                    class="text-sm text-blue-600 hover:text-blue-700">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                                No invoices have been generated for this subscription yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: Usage -->
                <div x-show="tab === 'usage'" x-transition class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Live Bandwidth</h3>
                                <p class="text-sm text-gray-500 mt-1">Current PPPoE throughput and RRD-backed history for
                                    this subscription</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <select x-model="bandwidth.range" @change="loadBandwidthHistory()"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1h">1 hour</option>
                                    <option value="6h">6 hours</option>
                                    <option value="24h">24 hours</option>
                                    <option value="7d">7 days</option>
                                    <option value="30d">30 days</option>
                                </select>
                                <button type="button" @click="refreshLiveBandwidth(); loadBandwidthHistory();"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Refresh
                                </button>
                            </div>
                        </div>

                        <!-- Live Stats -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">RX</p>
                                <p class="mt-2 text-2xl font-bold text-blue-600"
                                    x-text="formatSpeed(bandwidth.live.rx_bps)"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">TX</p>
                                <p class="mt-2 text-2xl font-bold text-emerald-600"
                                    x-text="formatSpeed(bandwidth.live.tx_bps)"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Interface</p>
                                <p class="mt-2 text-lg font-semibold text-gray-900"
                                    x-text="bandwidth.live.interface_name || '—'"></p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Last sample</p>
                                <p class="mt-2 text-lg font-semibold text-gray-900"
                                    x-text="bandwidth.live.sampled_at || 'No sample'"></p>
                            </div>
                        </div>

                        <!-- Collection Status -->
                        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <template x-if="bandwidth.live.rrd_available">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        RRD data collecting
                                    </span>
                                </template>
                                <template x-if="!bandwidth.live.rrd_available">
                                    <span class="inline-flex items-center gap-1.5 text-gray-500">
                                        <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                        No RRD data yet
                                    </span>
                                </template>
                            </div>
                            <div x-show="bandwidth.live.last_sampled_at" class="text-gray-500">
                                Last collection: <span class="font-medium text-gray-700" x-text="bandwidth.live.sampled_at || '—'"></span>
                            </div>
                        </div>

                        <!-- Responsive RRD Line Graph -->
                        <div class="relative mt-5 overflow-hidden rounded-xl border border-gray-200 bg-slate-50">
                            <div x-show="bandwidth.historyLoading"
                                class="absolute right-4 top-4 z-10 inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5 text-xs text-gray-500 shadow-sm">
                                <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Updating
                            </div>

                            <svg viewBox="0 0 1000 300" role="img" aria-label="Download and upload bandwidth history"
                                class="block h-72 w-full select-none"
                                @mousemove="updateBandwidthTooltip($event)"
                                @mouseleave="bandwidth.tooltip.show = false">
                                <defs>
                                    <linearGradient id="bandwidth-rx-fill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#2563eb" stop-opacity=".22"></stop>
                                        <stop offset="100%" stop-color="#2563eb" stop-opacity=".02"></stop>
                                    </linearGradient>
                                    <linearGradient id="bandwidth-tx-fill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#059669" stop-opacity=".18"></stop>
                                        <stop offset="100%" stop-color="#059669" stop-opacity=".02"></stop>
                                    </linearGradient>
                                </defs>

                                <template x-for="y in [32, 90, 148, 206, 264]" :key="y">
                                    <line x1="56" :y1="y" x2="976" :y2="y" stroke="#e2e8f0"
                                        stroke-width="1"></line>
                                </template>
                                <line x1="56" y1="32" x2="56" y2="264" stroke="#cbd5e1" stroke-width="1"></line>
                                <line x1="56" y1="264" x2="976" y2="264" stroke="#cbd5e1" stroke-width="1"></line>

                                <text x="48" y="38" text-anchor="end" class="fill-slate-400 text-[11px]"
                                    x-text="formatSpeed(chartMax())"></text>
                                <text x="48" y="269" text-anchor="end" class="fill-slate-400 text-[11px]">0</text>

                                <template x-for="(segment, index) in chartSegments('rx_bps')" :key="'rx-area-' + index">
                                    <path :d="areaPath(segment)" fill="url(#bandwidth-rx-fill)"></path>
                                </template>
                                <template x-for="(segment, index) in chartSegments('tx_bps')" :key="'tx-area-' + index">
                                    <path :d="areaPath(segment)" fill="url(#bandwidth-tx-fill)"></path>
                                </template>
                                <template x-for="(segment, index) in chartSegments('rx_bps')" :key="'rx-line-' + index">
                                    <path :d="linePath(segment)" fill="none" stroke="#2563eb" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                </template>
                                <template x-for="(segment, index) in chartSegments('tx_bps')" :key="'tx-line-' + index">
                                    <path :d="linePath(segment)" fill="none" stroke="#059669" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round"></path>
                                </template>

                                <template x-for="label in axisLabels()" :key="label.x">
                                    <text :x="label.x" y="286" text-anchor="middle"
                                        class="fill-slate-400 text-[11px]" x-text="label.label"></text>
                                </template>

                                <g x-show="bandwidth.tooltip.show">
                                    <line :x1="bandwidth.tooltip.x" y1="32" :x2="bandwidth.tooltip.x" y2="264"
                                        stroke="#94a3b8" stroke-dasharray="4 4"></line>
                                    <rect :x="bandwidth.tooltip.x - 70" y="38" width="140" height="66" rx="8"
                                        fill="#0f172a" opacity=".94"></rect>
                                    <text :x="bandwidth.tooltip.x" y="57" text-anchor="middle"
                                        class="fill-white text-[11px] font-semibold"
                                        x-text="bandwidth.tooltip.time"></text>
                                    <text :x="bandwidth.tooltip.x" y="76" text-anchor="middle"
                                        class="fill-blue-300 text-[11px]"
                                        x-text="'RX ' + (bandwidth.tooltip.rx === null ? '—' : formatSpeed(bandwidth.tooltip.rx))"></text>
                                    <text :x="bandwidth.tooltip.x" y="94" text-anchor="middle"
                                        class="fill-emerald-300 text-[11px]"
                                        x-text="'TX ' + (bandwidth.tooltip.tx === null ? '—' : formatSpeed(bandwidth.tooltip.tx))"></text>
                                </g>
                            </svg>

                            <div x-show="!bandwidth.hasData && !bandwidth.historyLoading"
                                class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                <span class="rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-sm text-slate-500 shadow-sm">
                                    No bandwidth samples for this range
                                </span>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-5">
                            <div class="flex flex-wrap items-center gap-5 text-sm">
                                <span class="inline-flex items-center gap-2 text-gray-600">
                                    <span class="h-3 w-3 rounded-full bg-blue-600"></span>
                                    <span class="font-medium">Download (RX)</span>
                                    <span class="text-gray-500" x-text="formatSpeed(bandwidth.live.rx_bps)"></span>
                                </span>
                                <span class="inline-flex items-center gap-2 text-gray-600">
                                    <span class="h-3 w-3 rounded-full bg-emerald-600"></span>
                                    <span class="font-medium">Upload (TX)</span>
                                    <span class="text-gray-500" x-text="formatSpeed(bandwidth.live.tx_bps)"></span>
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Source: <span class="font-medium" x-text="bandwidth.live.source"></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Usage Summary</h3>
                            <p class="text-sm text-gray-500 mt-1">Real usage totals for
                                {{ $usageSummary['window'] ?? 'the current billing window' }}</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Used</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">
                                    {{ number_format($usageSummary['total_gb'] ?? 0, 2) }} GB</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Download</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">
                                    {{ number_format($usageSummary['download_gb'] ?? 0, 2) }} GB</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Upload</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">
                                    {{ number_format($usageSummary['upload_gb'] ?? 0, 2) }} GB</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Quota</p>
                                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $quotaLabel }}</p>
                            </div>
                        </div>
                        <div class="mt-5 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Usage progress</span>
                                <span
                                    class="text-gray-700">{{ $usageSummary['quota_gb'] > 0 ? number_format($usagePercent, 1) . '%' : 'Unlimited' }}</span>
                            </div>
                            <div class="w-full h-3 rounded-full bg-gray-200 overflow-hidden">
                                <div class="h-full rounded-full {{ $usagePercent > 90 ? 'bg-red-500' : ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                    style="width: {{ $usageSummary['quota_gb'] > 0 ? $usagePercent : 0 }}%"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 text-sm text-gray-600">
                                <div>
                                    <span class="font-medium text-gray-900">Sessions:</span>
                                    {{ $usageSummary['sessions'] ?? 0 }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Last activity:</span>
                                    {{ $usageSummary['last_activity'] ?? 'No usage yet' }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Peak session:</span>
                                    {{ number_format($usageSummary['peak_gb'] ?? 0, 2) }} GB at
                                    {{ $usageSummary['peak_time'] ?? 'No usage yet' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounting Sessions -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">RADIUS Accounting Sessions</h3>
                            <p class="text-sm text-gray-500 mt-1">Latest sessions from FreeRADIUS accounting for this PPP
                                username</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Started</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Stopped</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Duration</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Download</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Upload</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Total</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Router</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            IP Address</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Terminate Cause</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($usageSessions as $session)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">{{ $session['date'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">{{ $session['stopped_at'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $session['status'] === 'online' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                                    {{ ucfirst($session['status']) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">{{ $session['duration'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-medium text-green-600">{{ $session['download'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-medium text-blue-600">{{ $session['upload'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-semibold text-gray-900">{{ $session['total'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">{{ $session['router'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-mono text-gray-900">{{ $session['ip_address'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm text-gray-500">{{ $session['terminate_cause'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500">
                                                No RADIUS accounting sessions have been captured for this subscription yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: Auth Attempts -->
                <div x-show="tab === 'auth'" x-transition class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Recent RADIUS Auth Attempts</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Latest radpostauth rows for {{ $subscription['pppoe_username'] }}.
                                    Entries older than 20 minutes are pruned automatically.
                                </p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                                <p class="font-medium text-gray-900">Retention window</p>
                                <p class="mt-1">20 minutes maximum</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Attempted</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Result</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Username</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Reply</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Password</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($authAttempts as $attempt)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $attempt['authdate'] }}
                                                </div>
                                                <div class="text-xs text-gray-500">{{ $attempt['authdate_human'] }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $attempt['outcome_class'] }}">
                                                    {{ $attempt['outcome_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-mono text-gray-900">{{ $attempt['username'] }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900 break-words">
                                                    {{ $attempt['reply_summary'] }}</div>
                                                <div class="mt-1 text-xs text-gray-500 break-words">
                                                    {{ $attempt['reply_details'] }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm text-gray-600">{{ $attempt['password_state'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                                No authentication attempts have been recorded in the last 20 minutes for
                                                this subscription.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-500">
                                Showing {{ $authAttempts->firstItem() ?? 0 }} to {{ $authAttempts->lastItem() ?? 0 }} of
                                {{ $authAttempts->total() }} attempts
                            </p>
                            @if ($authAttempts->hasPages())
                                {{ $authAttempts->links() }}
                            @endif
                        </div>
                    </div>
                </div>

                <!-- TAB: Contract -->
                <div x-show="tab === 'contract'" x-transition>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Contract Details</h3>
                                <p class="text-sm text-gray-500 mt-1">Service contract information</p>
                            </div>
                            <dl class="space-y-4">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Contract Start</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($subscription['contract_start'])->format('M d, Y') }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Contract End</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($subscription['contract_end'])->format('M d, Y') }}</dd>
                                </div>
                                @php
                                    $remainingDays = max(
                                        0,
                                        \Carbon\Carbon::parse($subscription['contract_end'])->diffInDays(
                                            \Carbon\Carbon::now(),
                                        ),
                                    );
                                @endphp
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Remaining Days</dt>
                                    <dd
                                        class="text-sm font-medium {{ $remainingDays < 30 ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $remainingDays }} days
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Auto Renew</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ $subscription['auto_renew'] ? 'Yes' : 'No' }}
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Last Billed</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($subscription['last_billing_date'])->format('M d, Y') }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Customer Information</h3>
                                <p class="text-sm text-gray-500 mt-1">Account holder details</p>
                            </div>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $subscription['customer']['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $subscription['customer']['id'] }}</p>
                                </div>
                            </div>
                            <dl class="space-y-3 pt-4 border-t border-gray-200">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm text-gray-900">{{ $subscription['customer']['email'] }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    <span class="text-sm text-gray-900">{{ $subscription['customer']['phone'] }}</span>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- TAB: Invoices -->
                <div x-show="tab === 'invoices'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Invoice History</h3>
                            <p class="text-sm text-gray-500 mt-1">Complete invoice history for this subscription</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Invoice Number</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Amount</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Balance Due</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Due Date</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Paid Date</th>
                                        <th
                                            class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($billingInvoices as $invoice)
                                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ $invoice['url'] }}"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-700">{{ $invoice['invoice_number'] }}</a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-medium text-gray-900">${{ number_format($invoice['amount'], 2) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm font-medium {{ $invoice['balance_due'] > 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($invoice['balance_due'], 2) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm text-gray-900">{{ $invoice['due_date'] ? \Carbon\Carbon::parse($invoice['due_date'])->format('M d, Y') : '—' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($invoice['status']) }}">{{ ucfirst($invoice['status']) }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="text-sm text-gray-900">{{ $invoice['paid_date'] ? \Carbon\Carbon::parse($invoice['paid_date'])->format('M d, Y') : '—' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <a href="{{ $invoice['url'] }}"
                                                    class="text-sm text-blue-600 hover:text-blue-700">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                                No invoices have been generated for this subscription yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: Activity Log -->
                <div x-show="tab === 'activity'" x-transition>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Activity Log</h3>
                            <p class="text-sm text-gray-500 mt-1">Subscription history and changes</p>
                        </div>
                        <x-activity-log :activities="$activityLog" />
                    </div>
                </div>
            </div>
        </div>

        <div x-show="credentials.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
            @keydown.escape.window="closeCredentialsModal()">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closeCredentialsModal()"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Manage Credentials</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ $subscription['subscription_code'] }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">Update PPPoE username and password without changing the rest
                            of the subscription.</p>
                    </div>
                    <button type="button" @click="closeCredentialsModal()"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('subscriptions.update', $subscription['id']) }}"
                    class="space-y-5 px-6 py-5" @submit="credentials.submitting = true" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="show_pppoe_username" class="block text-sm font-medium text-gray-700">PPP
                            Username</label>
                        <input type="text" name="pppoe_username" id="show_pppoe_username"
                            x-model="credentials.form.pppoe_username" autocomplete="off"
                            class="mt-1 block w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('pppoe_username')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="show_pppoe_password" class="block text-sm font-medium text-gray-700">PPP
                            Password</label>
                        <input type="password" name="pppoe_password" id="show_pppoe_password"
                            x-model="credentials.form.pppoe_password" autocomplete="new-password"
                            class="mt-1 block w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('pppoe_password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-teal-50 px-4 py-3 text-sm text-teal-800">
                        Saving either field will sync RADIUS and disconnect the current PPP session through RouterOS API.
                    </div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs text-gray-500">Leave a value unchanged if you do not want to alter it.</div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="closeCredentialsModal()"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-teal-700">
                                Save Credentials
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="ipChange.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="closeIpModal()"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Change IP</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900">{{ $subscription['subscription_code'] }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">Keep the subscription and IPAM inventory in sync.</p>
                    </div>
                    <button type="button" @click="closeIpModal()"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('subscriptions.update', $subscription['id']) }}"
                    class="space-y-5 px-6 py-5" @submit="ipChange.submitting = true">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="show_ip_address" class="block text-sm font-medium text-gray-700">IP Address</label>
                        <input type="text" name="ip_address" id="show_ip_address" x-model="ipChange.form.ip_address"
                            placeholder="192.168.1.100"
                            class="mt-1 block w-full rounded-xl border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('ip_address')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div
                        class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                        <div class="flex items-center justify-between gap-4">
                            <span>Current IP</span>
                            <span class="font-mono text-gray-900">{{ $subscription['ip_address'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span>Mode</span>
                            <span
                                class="font-medium text-gray-900">{{ $subscriptionModel->isSystemManagedIp() ? 'System managed' : 'Router managed' }}</span>
                        </div>
                        @if ($subscriptionModel->ipPool)
                            <div class="flex items-center justify-between gap-4">
                                <span>Free IPs</span>
                                <span
                                    class="font-medium text-gray-900">{{ $subscriptionModel->ipPool->available_ips }}</span>
                            </div>
                        @endif
                        <p class="text-xs text-gray-500">You can enter an IP manually or suggest a free one from the
                            current pool.</p>
                        <p x-show="ipChange.message" class="text-xs text-emerald-700" x-text="ipChange.message"></p>
                        <p x-show="ipChange.error" class="text-xs text-red-600" x-text="ipChange.error"></p>
                    </div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" @click="suggestIpAddress()"
                            :disabled="ipChange.suggesting || !{{ $canSuggestIp ? 'true' : 'false' }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60">
                            <svg x-show="! ipChange.suggesting" class="h-4 w-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <svg x-show="ipChange.suggesting" class="h-4 w-4 animate-spin" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="ipChange.suggesting ? 'Finding...' : 'Suggest free IP'"></span>
                        </button>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="closeIpModal()"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                                Save IP
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
