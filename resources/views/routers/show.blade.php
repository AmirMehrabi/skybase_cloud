@extends('layouts.admin')

@section('title', $router->name ?? 'Router Dashboard')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="routerShow" x-cloak>
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
                              :class="router.status === 'online' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
                              x-text="router.status.charAt(0).toUpperCase() + router.status.slice(1)"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                        <span x-text="router.ip_address"></span>
                        <span x-text="router.model"></span>
                        <span x-text="router.version"></span>
                        <span x-text="router.uptime"></span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="reconnectRouter()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reconnect
                </button>
                <button @click="restartRouter()" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-700 border border-yellow-200 rounded-lg text-sm font-medium hover:bg-yellow-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Restart Router
                </button>
                <a :href="`/routers/${router.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Router
                </a>
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
            <p class="text-2xl font-bold text-gray-900" x-text="router.cpu_usage + '%'"></p>
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-500"
                     :class="router.cpu_usage > 80 ? 'bg-red-500' : (router.cpu_usage > 60 ? 'bg-yellow-500' : 'bg-blue-500')"
                     :style="`width: ${router.cpu_usage}%`"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Memory Usage</span>
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="router.memory_usage + '%'"></p>
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-500"
                     :class="router.memory_usage > 80 ? 'bg-red-500' : (router.memory_usage > 60 ? 'bg-yellow-500' : 'bg-green-500')"
                     :style="`width: ${router.memory_usage}%`"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Active Sessions</span>
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="router.active_sessions_count"></p>
            <p class="text-xs text-gray-500 mt-3">Connected users</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Total Customers</span>
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <p class="text-2xl font-bold text-gray-900" x-text="router.total_customers"></p>
            <p class="text-xs text-gray-500 mt-3">Assigned to router</p>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm" x-data="{ activeTab: 'overview' }">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto" role="tablist">
                <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Overview
                </button>
                <button @click="activeTab = 'sessions'"
                        :class="activeTab === 'sessions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Sessions
                </button>
                <button @click="activeTab = 'queues'"
                        :class="activeTab === 'queues' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Queues
                </button>
                <button @click="activeTab = 'profiles'"
                        :class="activeTab === 'profiles' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Profiles
                </button>
                <button @click="activeTab = 'interfaces'"
                        :class="activeTab === 'interfaces' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Interfaces
                </button>
                <button @click="activeTab = 'ip-pools'"
                        :class="activeTab === 'ip-pools' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    IP Pools
                </button>
                <button @click="activeTab = 'provisioning'"
                        :class="activeTab === 'provisioning' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Provisioning
                </button>
                <button @click="activeTab = 'logs'"
                        :class="activeTab === 'logs' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Logs
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- TAB: Overview -->
            <div x-show="activeTab === 'overview'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Router Info -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Router Information</h3>
                        </div>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Name</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.name"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Model</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.model"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Vendor</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.vendor"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">IP Address</dt>
                                <dd class="text-sm font-medium text-gray-900 font-mono" x-text="router.ip_address"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Location</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.location"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Site</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.site"></dd>
                            </div>
                        </dl>
                    </div>

                    <!-- System Info -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
                        </div>
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">RouterOS Version</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.version"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Uptime</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.uptime"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">API Port</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.api_port"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">SSH Port</dt>
                                <dd class="text-sm font-medium text-gray-900" x-text="router.ssh_port"></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Monitoring</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="router.enable_monitoring ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                          x-text="router.enable_monitoring ? 'Enabled' : 'Disabled'"></span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Provisioning</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="router.enable_provisioning ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                          x-text="router.enable_provisioning ? 'Enabled' : 'Disabled'"></span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm lg:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Statistics</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-2xl font-bold text-blue-600" x-text="interfaces.length"></p>
                                <p class="text-xs text-gray-500 mt-1">Interfaces</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-2xl font-bold text-green-600" x-text="sessions.length"></p>
                                <p class="text-xs text-gray-500 mt-1">Active Sessions</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-2xl font-bold text-purple-600" x-text="queues.length"></p>
                                <p class="text-xs text-gray-500 mt-1">Queues</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-2xl font-bold text-indigo-600" x-text="profiles.length"></p>
                                <p class="text-xs text-gray-500 mt-1">Profiles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Sessions -->
            <div x-show="activeTab === 'sessions'" x-transition>
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Active PPP Sessions</h3>
                        <p class="text-sm text-gray-500">Real-time active connections</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Username</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">MAC Address</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Download</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Upload</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Session Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Interface</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="session in sessions" :key="session.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="session.username"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="session.customer"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="session.ip_address"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-500" x-text="session.mac_address"></td>
                                        <td class="px-4 py-3 text-sm text-green-600" x-text="session.download_speed"></td>
                                        <td class="px-4 py-3 text-sm text-blue-600" x-text="session.upload_speed"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="session.session_time"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="session.interface"></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button @click="disconnectSession(session)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg" title="Disconnect">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Queues -->
            <div x-show="activeTab === 'queues'" x-transition>
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Simple Queues</h3>
                        <p class="text-sm text-gray-500">Bandwidth management and rate limiting</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Queue Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Target IP</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Download Limit</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Upload Limit</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Burst Limit</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="queue in queues" :key="queue.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="queue.name"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="queue.target"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="queue.download_limit"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="queue.upload_limit"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="queue.burst_limit"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="queue.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
                                                  x-text="queue.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button @click="toggleQueueStatus(queue)" class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg" title="Toggle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                </button>
                                                <button @click="deleteQueue(queue)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Profiles -->
            <div x-show="activeTab === 'profiles'" x-transition>
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">PPP Profiles</h3>
                        <p class="text-sm text-gray-500">Speed profiles for customers</p>
                    </div>
                    <div class="flex justify-end mb-4">
                        <button @click="showCreateProfileModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Profile
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Profile Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Download Speed</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Upload Speed</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rate Limit</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Active Users</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="profile in profiles" :key="profile.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="profile.name"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="profile.download_speed"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="profile.upload_speed"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="profile.priority"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-500" x-text="profile.rate_limit"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="profile.active_users"></td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button @click="deleteProfile(profile)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Interfaces -->
            <div x-show="activeTab === 'interfaces'" x-transition>
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Router Interfaces</h3>
                        <p class="text-sm text-gray-500">Physical and virtual interfaces</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Interface Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">TX Rate</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">RX Rate</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">MAC Address</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="iface in interfaces" :key="iface.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="iface.name"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="iface.type"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="iface.status === 'active' || iface.status === 'running' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
                                                  x-text="iface.status"></span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-green-600" x-text="iface.tx_rate"></td>
                                        <td class="px-4 py-3 text-sm text-blue-600" x-text="iface.rx_rate"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-500" x-text="iface.mac_address"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: IP Pools -->
            <div x-show="activeTab === 'ip-pools'" x-transition>
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">IP Address Pools</h3>
                        <p class="text-sm text-gray-500">Manage IP address pools for PPPoE</p>
                    </div>
                    <div class="flex justify-end mb-4">
                        <button @click="showCreatePoolModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Pool
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pool Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Range Start</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Range End</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Used IPs</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Available IPs</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Usage</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="pool in ipPools" :key="pool.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="pool.name"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="pool.range_start"></td>
                                        <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="pool.range_end"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="pool.used_ips"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="pool.available_ips"></td>
                                        <td class="px-4 py-3 w-32">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="h-2.5 rounded-full bg-blue-500 transition-all duration-500"
                                                     :style="`width: ${(pool.used_ips / (pool.used_ips + pool.available_ips)) * 100}%`"></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button @click="deletePool(pool)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: Provisioning -->
            <div x-show="activeTab === 'provisioning'" x-transition>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Quick Provision -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Provision Customer</h3>
                            <p class="text-sm text-gray-500">Provision a new customer service</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
                                <select x-model="provisioning.customer" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select a customer</option>
                                    <template x-for="customer in availableCustomers" :key="customer.id">
                                        <option :value="customer.id" x-text="customer.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Plan</label>
                                <select x-model="provisioning.plan" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select a plan</option>
                                    <template x-for="plan in availablePlans" :key="plan.id">
                                        <option :value="plan.id" x-text="plan.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Profile</label>
                                <select x-model="provisioning.profile" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select a profile</option>
                                    <template x-for="profile in profiles" :key="profile.id">
                                        <option :value="profile.id" x-text="profile.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">IP Pool</label>
                                <select x-model="provisioning.ipPool" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select an IP pool</option>
                                    <template x-for="pool in ipPools" :key="pool.id">
                                        <option :value="pool.id" x-text="pool.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Router Interface</label>
                                <select x-model="provisioning.interface" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select an interface</option>
                                    <template x-for="iface in interfaces.filter(i => i.status === 'active')" :key="iface.id">
                                        <option :value="iface.name" x-text="iface.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" x-model="provisioning.enableService" class="h-4 w-4 text-blue-600 rounded border-gray-300">
                                <label class="text-sm text-gray-700">Enable Service Immediately</label>
                            </div>
                            <button @click="provisionCustomer()" :disabled="provisioning" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="!provisioning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <svg x-show="provisioning" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span x-text="provisioning ? 'Provisioning...' : 'Provision Now'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Provisioning Templates -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Provisioning Templates</h3>
                            <p class="text-sm text-gray-500">Pre-configured service templates</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Template</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Speed</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Priority</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customers</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="template in provisioningTemplates" :key="template.id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="template.name"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="template.download_speed + ' / ' + template.upload_speed"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="template.priority"></td>
                                            <td class="px-4 py-3 text-sm text-gray-700" x-text="template.assigned_customers"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bulk Provision -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm lg:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Bulk Provision</h3>
                            <p class="text-sm text-gray-500">Apply plan to multiple customers</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Plan</label>
                                <select x-model="bulkProvision.plan" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select a plan</option>
                                    <template x-for="plan in availablePlans" :key="plan.id">
                                        <option :value="plan.id" x-text="plan.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Router</label>
                                <select disabled class="block w-full rounded-lg border-gray-300 bg-gray-50 sm:text-sm py-2.5 px-3 border">
                                    <option x-text="router.name + ' (' + router.ip_address + ')'"></option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Profile</label>
                                <select x-model="bulkProvision.profile" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                                    <option value="">Select a profile</option>
                                    <template x-for="profile in profiles" :key="profile.id">
                                        <option :value="profile.id" x-text="profile.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button @click="bulkProvisionCustomers()" :disabled="bulkProvisioning" class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 text-white rounded-lg text-sm font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="!bulkProvisioning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <svg x-show="bulkProvisioning" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span x-text="bulkProvisioning ? 'Applying...' : 'Apply to All Pending Customers'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Logs -->
            <div x-show="activeTab === 'logs'" x-transition>
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Router Logs</h3>
                        <p class="text-sm text-gray-500">System and event logs</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Timestamp</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Event Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Message</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Severity</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="log in logs" :key="log.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-500" x-text="log.timestamp"></td>
                                        <td class="px-4 py-3 text-sm text-gray-700" x-text="log.event_type"></td>
                                        <td class="px-4 py-3 text-sm text-gray-900" x-text="log.message"></td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="log.severity === 'error' ? 'bg-red-100 text-red-800 border-red-200' : (log.severity === 'warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' : 'bg-blue-100 text-blue-800 border-blue-200')"
                                                  x-text="log.severity"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/routers/show-data.js') }}"></script>
<script src="{{ asset('js/routers/show.js') }}"></script>
@endscripts
@endsection
