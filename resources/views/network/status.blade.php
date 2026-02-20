@extends('layouts.admin')

@section('title', 'Network Status')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="networkStatus()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Network Status Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">NOC overview of network health and performance</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-2 bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm font-medium">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                Live Status
            </span>
            <button @click="refreshData()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Status Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        <!-- Total Routers -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Routers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.totalRouters"></p>
                    <p class="text-xs text-gray-500 mt-2">Managed devices</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Online Routers -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Online</p>
                    <p class="text-3xl font-bold text-green-600 mt-2" x-text="stats.onlineRouters"></p>
                    <p class="text-xs text-green-600 mt-2" x-text="Math.round((stats.onlineRouters / stats.totalRouters) * 100) + '% of total'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Offline Routers -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Offline</p>
                    <p class="text-3xl font-bold text-red-600 mt-2" x-text="stats.offlineRouters"></p>
                    <p class="text-xs text-red-600 mt-2" x-show="stats.offlineRouters > 0">Requires attention</p>
                    <p class="text-xs text-gray-500 mt-2" x-show="stats.offlineRouters === 0">All systems operational</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Active Sessions</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.activeSessions"></p>
                    <p class="text-xs text-gray-500 mt-2">Connected users</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Network Alerts -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Network Alerts</p>
                    <p class="text-3xl font-bold" :class="stats.alerts > 0 ? 'text-orange-600' : 'text-gray-900'" x-text="stats.alerts"></p>
                    <p class="text-xs text-gray-500 mt-2">Active notifications</p>
                </div>
                <div class="w-14 h-14 rounded-xl" :class="stats.alerts > 0 ? 'bg-orange-50 text-orange-600' : 'bg-green-50 text-green-600'" flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alert Banner (shown when there are offline routers) -->
    <div x-show="stats.offlineRouters > 0" class="bg-red-50 border border-red-200 rounded-2xl p-6" style="display: none;">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-red-900">Network Warning</h3>
                <p class="text-sm text-red-700 mt-1">
                    <span x-text="stats.offlineRouters"></span> router(s) are currently offline. This may affect network performance.
                </p>
            </div>
            <button @click="showOfflineOnly = !showOfflineOnly" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
                View Offline
            </button>
        </div>
    </div>

    <!-- Router Status Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Router Status</h3>
                <p class="text-sm text-gray-500">Real-time status and metrics for all routers</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="filterStatus = ''" class="px-3 py-1.5 text-sm font-medium rounded-lg"
                        :class="filterStatus === '' ? 'bg-blue-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'">
                    All
                </button>
                <button @click="filterStatus = 'online'" class="px-3 py-1.5 text-sm font-medium rounded-lg"
                        :class="filterStatus === 'online' ? 'bg-green-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'">
                    Online
                </button>
                <button @click="filterStatus = 'offline'" class="px-3 py-1.5 text-sm font-medium rounded-lg"
                        :class="filterStatus === 'offline' ? 'bg-red-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'">
                    Offline
                </button>
                <button @click="filterStatus = 'warning'" class="px-3 py-1.5 text-sm font-medium rounded-lg"
                        :class="filterStatus === 'warning' ? 'bg-yellow-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'">
                    Warning
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">CPU Usage</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Memory Usage</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Sessions</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Uptime</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Seen</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="router in filteredRouters" :key="router.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="router.name"></span>
                                    <span class="text-xs text-gray-500" x-text="router.location"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 font-mono" x-text="router.ipAddress"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span x-show="router.status === 'online'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" style="display: none;"></span>
                                        <span class="relative inline-flex rounded-full h-2.5 w-2.5"
                                              :class="router.status === 'online' ? 'bg-green-500' : (router.status === 'offline' ? 'bg-red-500' : 'bg-yellow-500')"></span>
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                          :class="router.status === 'online' ? 'bg-green-100 text-green-800 border-green-200' : (router.status === 'offline' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200')"
                                          x-text="router.status.charAt(0).toUpperCase() + router.status.slice(1)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-full">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-500">CPU</span>
                                        <span class="text-xs font-medium"
                                              :class="router.cpu > 80 ? 'text-red-600' : (router.cpu > 60 ? 'text-yellow-600' : 'text-gray-700')"
                                              x-text="router.cpu + '%'"></span>
                                    </div>
                                    <div class="w-20 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all duration-300"
                                             :class="router.cpu > 80 ? 'bg-red-500' : (router.cpu > 60 ? 'bg-yellow-500' : 'bg-green-500')"
                                             :style="`width: ${router.cpu}%`"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-full">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-500">MEM</span>
                                        <span class="text-xs font-medium"
                                              :class="router.memory > 80 ? 'text-red-600' : (router.memory > 60 ? 'text-yellow-600' : 'text-gray-700')"
                                              x-text="router.memory + '%'"></span>
                                    </div>
                                    <div class="w-20 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-blue-500 transition-all duration-300"
                                             :style="`width: ${router.memory}%`"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="router.activeSessions"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="router.uptime || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-500" x-text="router.lastSeen"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Alerts</h3>
            <p class="text-sm text-gray-500">System notifications and warnings</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Severity</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Message</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="alert in alerts" :key="alert.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="alert.time"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900" x-text="alert.router"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="alert.severity === 'critical' ? 'bg-red-100 text-red-800 border-red-200' : (alert.severity === 'warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-blue-100 text-blue-800 border-blue-200')"
                                      x-text="alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="alert.message"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="alerts.length === 0" style="display: none;">
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">No alerts - All systems normal</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@scripts
<script>
function networkStatus() {
    return {
        filterStatus: '',
        showOfflineOnly: false,
        stats: {
            totalRouters: 20,
            onlineRouters: 18,
            offlineRouters: 2,
            activeSessions: 1847,
            alerts: 5
        },
        routers: [],
        alerts: [],

        init() {
            this.generateRouters();
            this.generateAlerts();
        },

        get filteredRouters() {
            if (this.filterStatus === '') {
                return this.routers;
            }
            return this.routers.filter(r => r.status === this.filterStatus);
        },

        generateRouters() {
            const locations = ['Main Data Center', 'Downtown', 'West Tower', 'East Campus', 'North Building', 'South Wing', 'Central Hub'];
            const statuses = ['online', 'online', 'online', 'online', 'online', 'warning', 'offline'];

            for (let i = 0; i < 20; i++) {
                const status = i < 17 ? 'online' : (i < 19 ? 'warning' : 'offline');
                const cpu = status === 'offline' ? 0 : Math.floor(Math.random() * 60) + 20;
                const memory = status === 'offline' ? 0 : Math.floor(Math.random() * 50) + 30;

                this.routers.push({
                    id: i + 1,
                    name: `Router-${locations[i % locations.length].split(' ')[0]}-${String(i + 1).padStart(2, '0')}`,
                    location: locations[i % locations.length],
                    ipAddress: `192.168.${Math.floor(i / 5) + 1}.${(i % 5) * 10 + 1}`,
                    status: status,
                    cpu: cpu,
                    memory: memory,
                    activeSessions: status === 'offline' ? 0 : Math.floor(Math.random() * 200) + 50,
                    uptime: status === 'offline' ? null : `${Math.floor(Math.random() * 300) + 1}d ${Math.floor(Math.random() * 24)}h`,
                    lastSeen: status === 'online' ? 'Just now' : `${Math.floor(Math.random() * 60) + 1}m ago`
                });
            }
        },

        generateAlerts() {
            const messages = [
                { router: 'Router-West-03', severity: 'critical', message: 'Router offline - Connection timeout' },
                { router: 'Router-East-04', severity: 'warning', message: 'High CPU usage (87%) - Performance degradation' },
                { router: 'Router-Main-01', severity: 'info', message: 'Scheduled maintenance completed successfully' },
                { router: 'Router-North-05', severity: 'warning', message: 'Memory usage above 80% threshold' },
                { router: 'Router-Downtown-02', severity: 'critical', message: 'Router offline - No response to ping' }
            ];

            this.alerts = messages.map((m, i) => ({
                id: i + 1,
                time: this.getRandomTime(),
                router: m.router,
                severity: m.severity,
                message: m.message
            }));
        },

        getRandomTime() {
            const now = new Date();
            const past = new Date(now - Math.random() * 2 * 60 * 60 * 1000);
            return past.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        },

        refreshData() {
            this.routers = [];
            this.alerts = [];
            this.generateRouters();
            this.generateAlerts();
        }
    };
}
</script>
@endscripts
@endsection
