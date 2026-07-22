@extends('layouts.admin')

@section('title', 'Bandwidth Monitoring')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="bandwidthMonitoring()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bandwidth Monitoring</h1>
            <p class="text-sm text-gray-500 mt-1">Real-time and historical bandwidth usage across routers</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="liveMode = !liveMode" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="liveMode ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span x-text="liveMode ? 'Live Mode On' : 'Live Mode Off'"></span>
            </button>
            <button @click="refreshData()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Network Throughput -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Throughput</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatSpeed(stats.totalThroughput)"></p>
                    <p class="text-xs text-gray-500 mt-2">Combined up/down</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Download Throughput -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Download</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2" x-text="formatSpeed(stats.downloadThroughput)"></p>
                    <p class="text-xs text-gray-500 mt-2">Current collected traffic</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Upload Throughput -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Upload</p>
                    <p class="text-3xl font-bold text-green-600 mt-2" x-text="formatSpeed(stats.uploadThroughput)"></p>
                    <p class="text-xs text-gray-500 mt-2">Current collected traffic</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Peak Usage Today -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Peak Usage Today</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatSpeed(stats.peakUsage)"></p>
                    <p class="text-xs text-gray-500 mt-2" x-text="stats.peakTime"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Bandwidth Chart Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Bandwidth Utilization</h3>
                <p class="text-sm text-gray-500">Real-time traffic across all routers</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm text-gray-600">Download</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-sm text-gray-600">Upload</span>
                </div>
            </div>
        </div>

        <div x-show="!stats.rrdAvailable" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Historical chart storage is unavailable because RRDTool is not installed or configured.
        </div>

        <!-- Chart Area -->
        <div class="h-80 relative bg-gray-50 rounded-xl overflow-hidden">
            <!-- Grid Lines -->
            <div class="absolute inset-0 flex flex-col justify-between p-4">
                <div class="border-b border-gray-200 border-dashed"></div>
                <div class="border-b border-gray-200 border-dashed"></div>
                <div class="border-b border-gray-200 border-dashed"></div>
                <div class="border-b border-gray-200 border-dashed"></div>
            </div>

            <!-- Y-axis labels -->
            <div class="absolute left-0 top-0 bottom-0 flex flex-col justify-between py-4 pl-2 text-xs text-gray-400">
                <span x-text="formatSpeed(chartMax)"></span>
                <span x-text="formatSpeed(chartMax * .75)"></span>
                <span x-text="formatSpeed(chartMax * .5)"></span>
                <span x-text="formatSpeed(chartMax * .25)"></span>
                <span>0</span>
            </div>

            <div x-show="hasData && chartData.length" class="absolute inset-0 flex items-end justify-between gap-1 px-8 pb-4 pt-4 pl-12">
                <template x-for="(point, index) in chartData" :key="index">
                    <div class="flex h-full flex-1 max-w-8 flex-col items-center justify-end gap-1">
                        <div class="flex h-full w-full items-end gap-px">
                            <div class="w-1/2 rounded-t bg-blue-500 transition-all duration-300 hover:bg-blue-600"
                                 :style="`height: ${barHeight(point.rx_bps)}%`"
                                 :title="`Download: ${formatSpeed(point.rx_bps)}`"></div>
                            <div class="w-1/2 rounded-t bg-green-500 transition-all duration-300 hover:bg-green-600"
                                 :style="`height: ${barHeight(point.tx_bps)}%`"
                                 :title="`Upload: ${formatSpeed(point.tx_bps)}`"></div>
                        </div>
                        <span class="text-xs text-gray-500 rotate-45 origin-bottom-left mt-1" x-text="point.time"></span>
                    </div>
                </template>
            </div>
            <div x-show="!hasData" class="flex h-full items-center justify-center px-6 text-center text-sm text-gray-500">
                No bandwidth samples are available for the selected history range.
            </div>
        </div>
        <p class="mt-3 text-xs text-gray-500">Last sample: <span x-text="stats.lastSampledAt"></span></p>
    </div>

    <!-- Router Bandwidth Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Router Bandwidth</h3>
            <p class="text-sm text-gray-500">Current bandwidth usage per router</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Interface</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Download Speed</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Upload Speed</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Peak Speed</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Capacity</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilization</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="router in routerBandwidth" :key="router.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="router.name"></span>
                                    <span class="text-xs text-gray-500" x-text="router.ipAddress"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-700" x-text="router.interface"></span>
                                    <span class="text-xs text-gray-500" x-text="router.sampledAt || 'No sample'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-blue-600" x-text="formatSpeed(router.download)"></span>
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                    </svg>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-green-600" x-text="formatSpeed(router.upload)"></span>
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="formatSpeed(router.peak)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="formatSpeed(router.capacity)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-full">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-500">Utilization</span>
                                        <span class="text-xs font-medium"
                                              :class="router.utilization > 80 ? 'text-red-600' : (router.utilization > 60 ? 'text-yellow-600' : 'text-green-600')"
                                              x-text="router.utilization === null ? '—' : router.utilization + '%'"></span>
                                    </div>
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="router.utilization > 80 ? 'bg-red-500' : (router.utilization > 60 ? 'bg-yellow-500' : 'bg-green-500')"
                                             :style="`width: ${Math.min(router.utilization || 0, 100)}%`"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="statusClass(router.status)"
                                      x-text="router.status.charAt(0).toUpperCase() + router.status.slice(1)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Interface Utilization Section -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Interface Utilization</h3>
            <p class="text-sm text-gray-500">Detailed interface bandwidth usage</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Interface Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Capacity</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Usage</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usage %</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="iface in interfaces" :key="iface.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full"
                                         :class="iface.status === 'active' ? 'bg-green-500' : (iface.status === 'warning' ? 'bg-yellow-500' : 'bg-red-500')"></div>
                                    <span class="text-sm font-medium text-gray-900" x-text="iface.name"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="iface.subscription"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="iface.router"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="formatSpeed(iface.capacity)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900" x-text="formatSpeed(iface.usage)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-full">
                                    <div class="w-40 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="iface.usagePercent > 80 ? 'bg-red-500' : (iface.usagePercent > 60 ? 'bg-yellow-500' : 'bg-green-500')"
                                             :style="`width: ${Math.min(iface.usagePercent || 0, 100)}%`"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1" x-text="iface.usagePercent === null ? '—' : iface.usagePercent + '%'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="statusClass(iface.status)"
                                      x-text="iface.status.charAt(0).toUpperCase() + iface.status.slice(1)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function bandwidthMonitoring() {
    return {
        liveMode: false,
        interval: null,
        loading: false,
        stats: @js($networkBandwidth['stats']),
        chartData: @js($networkBandwidth['chartData']),
        hasData: @js($networkBandwidth['hasData']),
        routerBandwidth: @js($networkBandwidth['routerBandwidth']),
        interfaces: @js($networkBandwidth['interfaces']),

        init() {
            this.$watch('liveMode', (value) => {
                if (value) {
                    this.interval = setInterval(() => {
                        this.refreshData();
                    }, 10000);
                } else {
                    clearInterval(this.interval);
                }
            });
        },

        formatSpeed(bps) {
            if (bps === null || bps === undefined || Number(bps) === 0) return '0 bps';
            const k = 1000;
            const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
            const i = Math.min(sizes.length - 1, Math.floor(Math.log(Math.abs(bps)) / Math.log(k)));
            return parseFloat((bps / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        get chartMax() {
            const values = this.chartData.flatMap((point) => [point.rx_bps || 0, point.tx_bps || 0]);
            return Math.max(1, ...values);
        },

        barHeight(value) {
            if (value === null || value === undefined) {
                return 0;
            }

            return Math.min(100, (Number(value) / this.chartMax) * 100);
        },

        statusClass(status) {
            if (status === 'optimal') {
                return 'bg-green-100 text-green-800 border-green-200';
            }

            if (status === 'warning') {
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            }

            if (status === 'critical') {
                return 'bg-red-100 text-red-800 border-red-200';
            }

            return 'bg-gray-100 text-gray-600 border-gray-200';
        },

        async refreshData() {
            this.loading = true;

            try {
                const response = await fetch(@js(route('network.bandwidth.data')), {
                    headers: { 'Accept': 'application/json' },
                });

                if (! response.ok) {
                    return;
                }

                const payload = await response.json();
                this.stats = payload.stats || this.stats;
                this.chartData = payload.chartData || [];
                this.hasData = Boolean(payload.hasData);
                this.routerBandwidth = payload.routerBandwidth || [];
                this.interfaces = payload.interfaces || [];
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
