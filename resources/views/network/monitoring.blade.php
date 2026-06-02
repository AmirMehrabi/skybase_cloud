@extends('layouts.admin')

@section('title', 'Network Monitoring')

@section('content')
<div class="space-y-6" x-data="networkMonitoring(@js($monitoring))" x-init="loadChart()">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Network Monitoring</h1>
            <p class="mt-1 text-sm text-gray-500">Packet loss, latency, uptime, and router health across the tenant network</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <select x-model="routerId" @change="loadChart()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All routers</option>
                <template x-for="router in routers" :key="router.id">
                    <option :value="router.id" x-text="router.name"></option>
                </template>
            </select>
            <select x-model="range" @change="loadChart()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="1h">1 hour</option>
                <option value="6h">6 hours</option>
                <option value="24h">24 hours</option>
                <option value="7d">7 days</option>
                <option value="30d">30 days</option>
            </select>
            <button type="button" @click="loadChart()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Refresh
            </button>
        </div>
    </div>

    <div x-show="!stats.rrdAvailable" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        RRDTool is not available on this server, so historical graph storage cannot be updated until the rrdtool binary is installed.
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Routers</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="stats.totalRouters"></p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Online</p>
            <p class="mt-2 text-2xl font-bold text-emerald-600" x-text="stats.onlineRouters"></p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Warning</p>
            <p class="mt-2 text-2xl font-bold text-amber-600" x-text="stats.warningRouters"></p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Offline</p>
            <p class="mt-2 text-2xl font-bold text-red-600" x-text="stats.offlineRouters"></p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Avg latency</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatMs(stats.avgLatency)"></p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Avg loss</p>
            <p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatPercent(stats.avgPacketLoss)"></p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Health Trend</h2>
                <p class="text-sm text-gray-500">Latency and packet loss from RRD history</p>
            </div>
            <p class="text-xs text-gray-500">Last sample: <span x-text="stats.lastSampledAt"></span></p>
        </div>

        <div class="relative h-80 overflow-hidden rounded-xl bg-gray-50">
            <template x-if="chartData.length > 1">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                    <polyline :points="linePoints('latency_ms')" fill="none" stroke="#2563eb" stroke-width="1.6" vector-effect="non-scaling-stroke"></polyline>
                    <polyline :points="linePoints('packet_loss_percent')" fill="none" stroke="#dc2626" stroke-width="1.6" vector-effect="non-scaling-stroke"></polyline>
                </svg>
            </template>
            <div x-show="chartData.length <= 1" class="flex h-full items-center justify-center px-6 text-center text-sm text-gray-500">
                No historical RRD samples are available for this range yet.
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-5 text-sm">
            <span class="inline-flex items-center gap-2 text-gray-600"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>Latency</span>
            <span class="inline-flex items-center gap-2 text-gray-600"><span class="h-2.5 w-2.5 rounded-full bg-red-600"></span>Packet loss</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Routers</h2>
            <p class="text-sm text-gray-500">Latest collected monitoring state</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Router</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Latency</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Loss</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Uptime</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Load</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Last sample</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="router in routers" :key="router.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a :href="router.url" class="text-sm font-semibold text-gray-900 hover:text-blue-600" x-text="router.name"></a>
                                <p class="mt-1 text-xs text-gray-500"><span x-text="router.ipAddress"></span> · <span x-text="router.site"></span></p>
                                <p x-show="router.error" class="mt-1 max-w-md text-xs text-amber-700" x-text="router.error"></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold capitalize"
                                      :class="statusClass(router.status)"
                                      x-text="router.status"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="formatMs(router.latencyMs)"></td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="formatPercent(router.packetLossPercent)"></td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="router.uptime || '—'"></td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span x-text="`${router.cpuUsage ?? 0}% CPU / ${router.memoryUsage ?? 0}% MEM`"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500" x-text="router.sampledAt"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function networkMonitoring(initial) {
    return {
        stats: initial.stats,
        routers: initial.routers,
        chartData: [],
        range: '24h',
        routerId: '',
        async loadChart() {
            const params = new URLSearchParams({ range: this.range });
            if (this.routerId) {
                params.set('router_id', this.routerId);
            }

            const response = await fetch(`{{ route('network.monitoring.data') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                const data = await response.json();
                this.chartData = data.chartData || [];
            }
        },
        maxValue(key) {
            return Math.max(1, ...this.chartData.map(point => Number(point[key] || 0)));
        },
        linePoints(key) {
            const max = this.maxValue(key);
            const last = Math.max(1, this.chartData.length - 1);

            return this.chartData.map((point, index) => {
                const x = (index / last) * 100;
                const y = 96 - ((Number(point[key] || 0) / max) * 88);
                return `${x},${Math.max(4, Math.min(96, y))}`;
            }).join(' ');
        },
        statusClass(status) {
            if (status === 'online') {
                return 'border-emerald-200 bg-emerald-50 text-emerald-700';
            }

            if (status === 'warning') {
                return 'border-amber-200 bg-amber-50 text-amber-700';
            }

            return 'border-red-200 bg-red-50 text-red-700';
        },
        formatMs(value) {
            return value === null || value === undefined ? '—' : `${Number(value).toFixed(1)} ms`;
        },
        formatPercent(value) {
            return value === null || value === undefined ? '—' : `${Number(value).toFixed(1)}%`;
        },
    };
}
</script>
@endpush
@endsection
