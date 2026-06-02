@extends('layouts.admin')

@section('title', $router->name ?? 'Router Dashboard')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="routerShow(@json($router), @json($netflowSummary))" x-init="loadHealth()" x-cloak>
    <!-- Top Header -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900" x-text="router.name"></h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                              :class="(router.status ?? 'offline') === 'online' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
                              x-text="(router.status ?? 'offline').charAt(0).toUpperCase() + (router.status ?? 'offline').slice(1)"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                        <span x-text="router.ip_address"></span>
                        <span x-show="router.model" x-text="router.model"></span>
                        <span x-show="router.version" x-text="router.version"></span>
                        <span x-show="router.uptime" x-text="router.uptime"></span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('routers.edit', $router) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Router
                </a>
                <button @click="confirmDelete()" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 text-red-700 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Resource Monitor Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">CPU Usage</span>
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="(router.cpu_usage ?? 0) + '%'"></p>
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-500"
                     :class="(router.cpu_usage ?? 0) > 80 ? 'bg-red-500' : ((router.cpu_usage ?? 0) > 60 ? 'bg-yellow-500' : 'bg-blue-500')"
                     :style="`width: ${router.cpu_usage ?? 0}%`"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Memory Usage</span>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="(router.memory_usage ?? 0) + '%'"></p>
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-500"
                     :class="(router.memory_usage ?? 0) > 80 ? 'bg-red-500' : ((router.memory_usage ?? 0) > 60 ? 'bg-yellow-500' : 'bg-green-500')"
                     :style="`width: ${router.memory_usage ?? 0}%`"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Active Sessions</span>
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="router.active_sessions_count ?? 0"></p>
            <p class="text-xs text-gray-500 mt-3">Connected users</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Total Customers</span>
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="router.total_customers ?? 0"></p>
            <p class="text-xs text-gray-500 mt-3">Assigned customers</p>
        </div>
    </div>

    <!-- Health Monitoring -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Health Monitoring</h3>
                <p class="text-sm text-gray-500 mt-1">Latency, packet loss, and resource history from RRD samples</p>
            </div>
            <select x-model="health.range" @change="loadHealth()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
                <option value="1h">1 hour</option>
                <option value="6h">6 hours</option>
                <option value="24h">24 hours</option>
                <option value="7d">7 days</option>
                <option value="30d">30 days</option>
            </select>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Latest latency</p>
                <p class="mt-2 text-2xl font-bold text-gray-900" x-text="latestHealth('latency_ms', 'ms')"></p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Packet loss</p>
                <p class="mt-2 text-2xl font-bold text-gray-900" x-text="latestHealth('packet_loss_percent', '%')"></p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Online signal</p>
                <p class="mt-2 text-2xl font-bold text-gray-900" x-text="latestOnlineLabel()"></p>
            </div>
        </div>

        <div class="mt-5 h-72 overflow-hidden rounded-xl bg-gray-50">
            <template x-if="health.chartData.length > 1">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="h-full w-full">
                    <polyline :points="healthLine('latency_ms')" fill="none" stroke="#2563eb" stroke-width="1.6" vector-effect="non-scaling-stroke"></polyline>
                    <polyline :points="healthLine('packet_loss_percent')" fill="none" stroke="#dc2626" stroke-width="1.6" vector-effect="non-scaling-stroke"></polyline>
                </svg>
            </template>
            <div x-show="health.chartData.length <= 1" class="flex h-full items-center justify-center px-6 text-center text-sm text-gray-500">
                No RRD history has been collected for this router yet.
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-5 text-sm">
            <span class="inline-flex items-center gap-2 text-gray-600"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>Latency</span>
            <span class="inline-flex items-center gap-2 text-gray-600"><span class="h-2.5 w-2.5 rounded-full bg-red-600"></span>Packet loss</span>
        </div>
    </div>

    <!-- Router Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Connection Details -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection Details</h3>
            <div class="space-y-4">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">IP Address</span>
                    <span class="text-sm font-medium text-gray-900 font-mono" x-text="router.ip_address"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">API Port</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.api_port"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">SSH Port</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.ssh_port"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">API Username</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.api_username || '—'"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Timeout</span>
                    <span class="text-sm font-medium text-gray-900" x-text="(router.timeout ?? 30) + ' seconds'"></span>
                </div>
            </div>
        </div>

        <!-- Router Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Router Information</h3>
            <div class="space-y-4">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Vendor</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.vendor"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Model</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.model || '—'"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Location</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.location || '—'"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Site</span>
                    <span class="text-sm font-medium text-gray-900" x-text="router.site || '—'"></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-500">Monitoring</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                          :class="router.enable_monitoring ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                          x-text="router.enable_monitoring ? 'Enabled' : 'Disabled'"></span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-sm text-gray-500">Provisioning</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                          :class="router.enable_provisioning ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                          x-text="router.enable_provisioning ? 'Enabled' : 'Disabled'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- NetFlow Monitor -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">NetFlow</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                          :class="router.netflow_enabled ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-700 border-gray-200'"
                          x-text="router.netflow_enabled ? 'Enabled' : 'Disabled'"></span>
                    <span x-show="router.netflow_setup_status" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100" x-text="router.netflow_setup_status"></span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    <span x-text="router.netflow_collector_host || 'No collector host'"></span>:<span x-text="router.netflow_collector_port || 2055"></span>
                    <span class="mx-2 text-gray-300">/</span>
                    <span x-text="'Version ' + (router.netflow_version || 9)"></span>
                    <span class="mx-2 text-gray-300">/</span>
                    <span x-text="'Interfaces ' + (router.netflow_interfaces || 'all')"></span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button @click="setupNetflow()" :disabled="netflow.settingUp || !isMikrotik()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span x-text="netflow.settingUp ? 'Configuring...' : 'Setup on MikroTik'"></span>
                </button>
                <button @click="testNetflow()" :disabled="netflow.testing || !router.netflow_enabled" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"></path>
                    </svg>
                    <span x-text="netflow.testing ? 'Testing...' : 'Test Connection'"></span>
                </button>
            </div>
        </div>

        <div x-show="netflow.message" class="mb-5 rounded-lg border px-4 py-3 text-sm"
             :class="netflow.ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
             x-text="netflow.message"></div>

        <div x-show="!isMikrotik()" class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
            NetFlow setup is only available for MikroTik routers.
        </div>

        <div x-show="isMikrotik()" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Flows Last Hour</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900" x-text="netflow.summary.stats.flows"></p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Throughput</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatBits(netflow.summary.stats.throughput_bps)"></p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Total Traffic</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatBytes(netflow.summary.stats.bytes)"></p>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Last Packet</p>
                    <p class="mt-2 text-lg font-semibold text-gray-900" x-text="netflow.summary.stats.last_packet_at || 'No packets'"></p>
                </div>
            </div>

            <div x-show="netflow.summary.stats.flows === 0" class="rounded-lg border border-dashed border-gray-300 p-6 text-center">
                <p class="text-sm font-medium text-gray-900">No NetFlow data received yet</p>
                <p class="text-sm text-gray-500 mt-1">Configure the router and run the collector with php artisan netflow:collect.</p>
            </div>

            <div x-show="netflow.summary.stats.flows > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Top Sources</h4>
                    <template x-for="item in netflow.summary.top_sources" :key="item.label">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-sm font-mono text-gray-700" x-text="item.label"></span>
                            <span class="text-sm font-medium text-gray-900" x-text="formatBytes(item.bytes)"></span>
                        </div>
                    </template>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Top Destinations</h4>
                    <template x-for="item in netflow.summary.top_destinations" :key="item.label">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-sm font-mono text-gray-700" x-text="item.label"></span>
                            <span class="text-sm font-medium text-gray-900" x-text="formatBytes(item.bytes)"></span>
                        </div>
                    </template>
                </div>
                <div class="rounded-lg border border-gray-200 p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Protocols</h4>
                    <template x-for="item in netflow.summary.top_protocols" :key="item.label">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                            <span class="text-sm text-gray-700" x-text="item.label"></span>
                            <span class="text-sm font-medium text-gray-900" x-text="formatBytes(item.bytes)"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="netflow.summary.latest_flows.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Destination</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Protocol</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bytes</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Packets</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Received</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <template x-for="flow in netflow.summary.latest_flows" :key="flow.id">
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono text-gray-900" x-text="flow.source"></td>
                                <td class="px-4 py-3 text-sm font-mono text-gray-900" x-text="flow.destination"></td>
                                <td class="px-4 py-3 text-sm text-gray-700" x-text="flow.protocol"></td>
                                <td class="px-4 py-3 text-sm text-gray-700" x-text="formatBytes(flow.bytes)"></td>
                                <td class="px-4 py-3 text-sm text-gray-700" x-text="flow.packets"></td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="flow.received_at"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a :href="'#'" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Active Sessions</p>
                    <p class="text-xs text-gray-500">View connected users</p>
                </div>
            </a>

            <a :href="`#`" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Queue Management</p>
                    <p class="text-xs text-gray-500">Manage bandwidth queues</p>
                </div>
            </a>

            <a :href="`#`" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Profile Profiles</p>
                    <p class="text-xs text-gray-500">Manage rate profiles</p>
                </div>
            </a>

            <a :href="`#`" class="flex items-center gap-3 p-4 rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                <div class="w-10 h-10 rounded-lg bg-yellow-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Interfaces</p>
                    <p class="text-xs text-gray-500">View network interfaces</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModal.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="deleteModal.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="deleteModal.show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Router</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to delete "<span x-text="router.name"></span>"? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="deleteRouter" :disabled="deleteModal.deleting" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!deleteModal.deleting">Delete</span>
                        <span x-show="deleteModal.deleting">Deleting...</span>
                    </button>
                    <button @click="deleteModal.show = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function routerShow(router, netflowSummary) {
    return {
        router: router,
        netflow: {
            summary: netflowSummary,
            settingUp: false,
            testing: false,
            message: '',
            ok: true
        },
        health: {
            range: '24h',
            chartData: []
        },
        deleteModal: {
            show: false,
            deleting: false
        },

        isMikrotik() {
            return String(this.router.vendor || '').toLowerCase() === 'mikrotik';
        },

        async setupNetflow() {
            this.netflow.settingUp = true;
            this.netflow.message = '';

            try {
                const response = await fetch(`{{ route('routers.index') }}/${this.router.id}/netflow/setup`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        netflow_enabled: Boolean(this.router.netflow_enabled),
                        netflow_collector_host: this.router.netflow_collector_host,
                        netflow_collector_port: this.router.netflow_collector_port || 2055,
                        netflow_version: this.router.netflow_version || 9,
                        netflow_interfaces: this.router.netflow_interfaces || 'all',
                        netflow_sampling_interval: this.router.netflow_sampling_interval || 1
                    })
                });
                const data = await response.json();

                this.netflow.ok = response.ok;
                this.netflow.message = data.message || 'NetFlow setup completed.';

                if (response.ok && data.router) {
                    this.router = data.router;
                    await this.refreshNetflow();
                }
            } catch (error) {
                this.netflow.ok = false;
                this.netflow.message = 'NetFlow setup failed. Please try again.';
            } finally {
                this.netflow.settingUp = false;
            }
        },

        async testNetflow() {
            this.netflow.testing = true;
            this.netflow.message = '';

            try {
                const response = await fetch(`{{ route('routers.index') }}/${this.router.id}/netflow/test`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                this.netflow.ok = response.ok;
                this.netflow.message = data.message || 'NetFlow test completed.';
                await this.refreshNetflow();
            } catch (error) {
                this.netflow.ok = false;
                this.netflow.message = 'NetFlow test failed. Please try again.';
            } finally {
                this.netflow.testing = false;
            }
        },

        async refreshNetflow() {
            const response = await fetch(`{{ route('routers.index') }}/${this.router.id}/netflow/data`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                this.netflow.summary = await response.json();
            }
        },

        async loadHealth() {
            const response = await fetch(`{{ route('routers.monitoring.data', $router) }}?range=${this.health.range}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.health.chartData = data.chartData || [];
            }
        },

        latestHealth(key, suffix) {
            const value = [...this.health.chartData].reverse().find(point => point[key] !== null)?.[key];

            return value === undefined ? '—' : `${Number(value).toFixed(1)} ${suffix}`;
        },

        latestOnlineLabel() {
            const value = [...this.health.chartData].reverse().find(point => point.online !== null)?.online;

            if (value === undefined) {
                return '—';
            }

            return Number(value) >= 0.5 ? 'Online' : 'Offline';
        },

        healthLine(key) {
            const max = Math.max(1, ...this.health.chartData.map(point => Number(point[key] || 0)));
            const last = Math.max(1, this.health.chartData.length - 1);

            return this.health.chartData.map((point, index) => {
                const x = (index / last) * 100;
                const y = 96 - ((Number(point[key] || 0) / max) * 88);

                return `${x},${Math.max(4, Math.min(96, y))}`;
            }).join(' ');
        },

        formatBytes(bytes) {
            const value = Number(bytes || 0);
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let size = value;
            let unit = 0;

            while (size >= 1024 && unit < units.length - 1) {
                size = size / 1024;
                unit++;
            }

            return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
        },

        formatBits(bits) {
            const value = Number(bits || 0);
            const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
            let size = value;
            let unit = 0;

            while (size >= 1000 && unit < units.length - 1) {
                size = size / 1000;
                unit++;
            }

            return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
        },

        confirmDelete() {
            this.deleteModal.show = true;
        },

        async deleteRouter() {
            this.deleteModal.deleting = true;
            try {
                const response = await fetch(`{{ route('routers.index') }}/${this.router.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.href = '{{ route('routers.index') }}';
                } else {
                    alert('Error deleting router. Please try again.');
                }
            } catch (error) {
                console.error('Error deleting router:', error);
                alert('Error deleting router. Please try again.');
            } finally {
                this.deleteModal.deleting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
