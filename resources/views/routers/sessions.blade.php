@extends('layouts.admin')

@section('title', 'Router Sessions')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="routerSessions" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Active Sessions</h1>
            <p class="text-sm text-gray-500 mt-1" x-text="`${router.name} - PPP Active Connections`"></p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="refreshSessions()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            <a :href="`/routers/${router.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <input type="text" x-model="filters.search" placeholder="Search by username, IP, MAC..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <select x-model="filters.interface" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Interfaces</option>
                <template x-for="iface in availableInterfaces" :key="iface">
                    <option :value="iface" x-text="iface"></option>
                </template>
            </select>
            <select x-model="filters.minSpeed" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Speeds</option>
                <option value="10">Min 10 Mbps</option>
                <option value="50">Min 50 Mbps</option>
                <option value="100">Min 100 Mbps</option>
                <option value="500">Min 500 Mbps</option>
            </select>
            <select x-model="filters.sessionTime" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Session Times</option>
                <option value="1h">Less than 1 hour</option>
                <option value="2h">Less than 2 hours</option>
                <option value="5h">Less than 5 hours</option>
            </select>
        </div>
    </div>

    <!-- Sessions Table -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="text-sm text-gray-500">
                Showing <span class="font-semibold text-gray-900" x-text="filteredSessions.length"></span> of
                <span class="font-semibold text-gray-900" x-text="sessions.length"></span> sessions
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100" @click="sortBy('username')">
                            Username <span x-show="sortColumn === 'username'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100" @click="sortBy('ip_address')">
                            IP Address <span x-show="sortColumn === 'ip_address'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">MAC Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100" @click="sortBy('download_speed')">
                            Download <span x-show="sortColumn === 'download_speed'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100" @click="sortBy('upload_speed')">
                            Upload <span x-show="sortColumn === 'upload_speed'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100" @click="sortBy('session_time')">
                            Session Time <span x-show="sortColumn === 'session_time'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Interface</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="session in paginatedSessions" :key="session.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="session.username"></td>
                            <td class="px-4 py-3 text-sm text-gray-700" x-text="session.customer"></td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-700" x-text="session.ip_address"></td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-500" x-text="session.mac_address"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-green-500 h-1.5 rounded-full" :style="`width: ${getSpeedPercentage(session.download_speed)}%`"></div>
                                    </div>
                                    <span class="text-sm text-green-600 whitespace-nowrap" x-text="session.download_speed"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-blue-500 h-1.5 rounded-full" :style="`width: ${getSpeedPercentage(session.upload_speed)}%`"></div>
                                    </div>
                                    <span class="text-sm text-blue-600 whitespace-nowrap" x-text="session.upload_speed"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="session.session_time"></td>
                            <td class="px-4 py-3 text-sm text-gray-700" x-text="session.interface"></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="suspendSession(session)" class="p-1.5 text-yellow-500 hover:bg-yellow-50 rounded-lg" title="Suspend">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                    <button @click="limitSpeed(session)" class="p-1.5 text-purple-500 hover:bg-purple-50 rounded-lg" title="Limit Speed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </button>
                                    <button @click="viewCustomer(session)" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg" title="View Customer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button @click="disconnectSession(session)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg" title="Disconnect">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="paginatedSessions.length === 0">
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">No sessions found</p>
                                <button @click="clearFilters()" x-show="hasActiveFilters" class="text-sm text-blue-600 hover:text-blue-700 font-medium" style="display: none;">
                                    Clear Filters
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="totalPages > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span x-text="(currentPage - 1) * perPage + 1"></span> to <span x-text="Math.min(currentPage * perPage, filteredSessions.length)"></span> of <span x-text="filteredSessions.length"></span> sessions
            </div>
            <div class="flex items-center gap-2">
                <button @click="prevPage()" :disabled="currentPage === 1" class="px-3 py-1 rounded border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                    Previous
                </button>
                <template x-for="page in visiblePages" :key="page">
                    <button @click="goToPage(page)" :class="currentPage === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1 rounded border text-sm min-w-[36px]" x-text="page"></button>
                </template>
                <button @click="nextPage()" :disabled="currentPage === totalPages" class="px-3 py-1 rounded border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/routers/show-data.js') }}"></script>
<script src="{{ asset('js/routers/sessions.js') }}"></script>
@endscripts
@endsection
