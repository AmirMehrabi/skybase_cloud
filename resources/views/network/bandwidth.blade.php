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
                    <div class="flex items-center gap-1 mt-2">
                        <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-xs text-green-600">+15.3%</span>
                    </div>
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
                    <div class="flex items-center gap-1 mt-2">
                        <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-xs text-green-600">+8.7%</span>
                    </div>
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
                <span>10 Gbps</span>
                <span>7.5 Gbps</span>
                <span>5 Gbps</span>
                <span>2.5 Gbps</span>
                <span>0</span>
            </div>

            <!-- Chart Bars -->
            <div class="absolute inset-0 flex items-end justify-between px-8 pb-4 pt-4 pl-12">
                <template x-for="(point, index) in chartData" :key="index">
                    <div class="flex flex-col items-center gap-1 flex-1 max-w-8">
                        <!-- Download Bar -->
                        <div class="w-full bg-blue-500 rounded-t transition-all duration-300 hover:bg-blue-600"
                             :style="`height: ${(point.download / 10000) * 100}%`"
                             :title="`Download: ${formatSpeed(point.download)}`"></div>
                        <!-- Upload Bar -->
                        <div class="w-full bg-green-500 rounded-b transition-all duration-300 hover:bg-green-600"
                             :style="`height: ${(point.upload / 10000) * 100}%`"
                             :title="`Upload: ${formatSpeed(point.upload)}`"></div>
                        <span class="text-xs text-gray-500 rotate-45 origin-bottom-left mt-1" x-text="point.time"></span>
                    </div>
                </template>
            </div>
        </div>
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
                                <span class="text-sm text-gray-700" x-text="router.interface"></span>
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
                                              x-text="router.utilization + '%'"></span>
                                    </div>
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="router.utilization > 80 ? 'bg-red-500' : (router.utilization > 60 ? 'bg-yellow-500' : 'bg-green-500')"
                                             :style="`width: ${router.utilization}%`"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="router.status === 'optimal' ? 'bg-green-100 text-green-800 border-green-200' : (router.status === 'warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-red-100 text-red-800 border-red-200')"
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
                                             :style="`width: ${iface.usagePercent}%`"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 mt-1" x-text="iface.usagePercent + '%'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="iface.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : (iface.status === 'warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-red-100 text-red-800 border-red-200')"
                                      x-text="iface.status.charAt(0).toUpperCase() + iface.status.slice(1)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@scripts
<script>
function bandwidthMonitoring() {
    return {
        liveMode: false,
        stats: {
            totalThroughput: 8543200000,
            downloadThroughput: 6789500000,
            uploadThroughput: 1753700000,
            peakUsage: 9234500000,
            peakTime: '14:32'
        },
        chartData: [],
        routerBandwidth: [],
        interfaces: [],

        init() {
            this.generateChartData();
            this.generateRouterBandwidth();
            this.generateInterfaces();

            // Auto-refresh in live mode
            this.$watch('liveMode', (value) => {
                if (value) {
                    this.interval = setInterval(() => {
                        this.refreshData();
                    }, 2000);
                } else {
                    clearInterval(this.interval);
                }
            });
        },

        generateChartData() {
            const times = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'];
            for (let i = 0; i < 24; i++) {
                const hour = Math.floor(i / 4);
                const time = times[hour % times.length];
                this.chartData.push({
                    time: time,
                    download: Math.floor(Math.random() * 8000000000) + 1000000000,
                    upload: Math.floor(Math.random() * 2000000000) + 200000000
                });
            }
        },

        generateRouterBandwidth() {
            const routers = [
                { name: 'Router-Main-01', ip: '192.168.1.1', interface: 'ether1' },
                { name: 'Router-Downtown-02', ip: '192.168.2.1', interface: 'ether2' },
                { name: 'Router-West-03', ip: '192.168.3.1', interface: 'ether1' },
                { name: 'Router-East-04', ip: '192.168.4.1', interface: 'ether3' },
                { name: 'Router-North-05', ip: '192.168.5.1', interface: 'ether1' }
            ];

            this.routerBandwidth = routers.map((r, i) => {
                const download = Math.floor(Math.random() * 3000000000) + 500000000;
                const upload = Math.floor(Math.random() * 800000000) + 100000000;
                const capacity = 10000000000;
                const utilization = Math.floor(((download + upload) / capacity) * 100);

                return {
                    id: i + 1,
                    name: r.name,
                    ipAddress: r.ip,
                    interface: r.interface,
                    download: download,
                    upload: upload,
                    peak: download + Math.floor(Math.random() * 1000000000),
                    capacity: capacity,
                    utilization: utilization,
                    status: utilization > 80 ? 'critical' : (utilization > 60 ? 'warning' : 'optimal')
                };
            });
        },

        generateInterfaces() {
            const interfaces = [
                { name: 'ether1-gateway', router: 'Router-Main-01' },
                { name: 'ether2-lan', router: 'Router-Main-01' },
                { name: 'ether3-wan', router: 'Router-Downtown-02' },
                { name: 'ether1-uplink', router: 'Router-West-03' },
                { name: 'ether2-backhaul', router: 'Router-East-04' },
                { name: 'ether1-core', router: 'Router-North-05' },
                { name: 'wlan1-main', router: 'Router-Main-01' },
                { name: 'wlan2-guest', router: 'Router-Downtown-02' }
            ];

            this.interfaces = interfaces.map((iface, i) => {
                const capacity = Math.floor(Math.random() * 9000000000) + 1000000000;
                const usage = Math.floor(capacity * (Math.random() * 0.7 + 0.1));
                const usagePercent = Math.floor((usage / capacity) * 100);

                return {
                    id: i + 1,
                    name: iface.name,
                    router: iface.router,
                    capacity: capacity,
                    usage: usage,
                    usagePercent: usagePercent,
                    status: usagePercent > 80 ? 'error' : (usagePercent > 60 ? 'warning' : 'active')
                };
            });
        },

        formatSpeed(bps) {
            if (bps === 0) return '0 bps';
            const k = 1000;
            const sizes = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
            const i = Math.floor(Math.log(bps) / Math.log(k));
            return parseFloat((bps / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        refreshData() {
            this.chartData = [];
            this.routerBandwidth = [];
            this.interfaces = [];
            this.generateChartData();
            this.generateRouterBandwidth();
            this.generateInterfaces();
        }
    };
}
</script>
@endscripts
@endsection
