@extends('layouts.admin')

@section('title', 'Data Usage')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="dataUsage()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Usage</h1>
            <p class="text-sm text-gray-500 mt-1">Monitor customer and network traffic consumption</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="exportCSV()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        <!-- Total Usage Today -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Usage Today</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(stats.totalToday)"></p>
                    <p class="text-xs text-gray-500 mt-2">+8.2% from yesterday</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Usage This Month -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Usage This Month</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(stats.totalMonth)"></p>
                    <p class="text-xs text-green-600 mt-2">+12.5% from last month</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.activeUsers"></p>
                    <p class="text-xs text-gray-500 mt-2">Currently online</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Top User Usage -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Top User Usage</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(stats.topUserUsage)"></p>
                    <p class="text-xs text-gray-500 mt-2" x-text="stats.topUserName"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Average Usage Per User -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Avg Usage Per User</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(stats.avgUsagePerUser)"></p>
                    <p class="text-xs text-gray-500 mt-2">Per active user</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Filters</h3>
            <button x-show="hasActiveFilters()" @click="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-medium" style="display: none;">
                Clear All
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date Range</label>
                <select x-model="filters.dateRange" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">Last 7 Days</option>
                    <option value="month" selected>This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Router</label>
                <select x-model="filters.router" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="">All Routers</option>
                    <template x-for="router in routerOptions" :key="router.value">
                        <option :value="router.value" x-text="router.label"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Customer</label>
                <select x-model="filters.customer" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="">All Customers</option>
                    <template x-for="customer in customerOptions" :key="customer.value">
                        <option :value="customer.value" x-text="customer.label"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Subscription</label>
                <select x-model="filters.subscription" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="">All Subscriptions</option>
                    <template x-for="sub in subscriptionOptions" :key="sub.value">
                        <option :value="sub.value" x-text="sub.label"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Usage Type</label>
                <select x-model="filters.usageType" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="">All Types</option>
                    <option value="download">Download</option>
                    <option value="upload">Upload</option>
                    <option value="total">Total</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Usage Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Customer Usage Details</h3>
            <p class="text-sm text-gray-500">Data consumption by customer</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Download</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Upload</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Session Time</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Activity</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="usage in filteredUsage" :key="usage.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="usage.customer"></span>
                                    <span class="text-xs text-gray-500" x-text="usage.customerId"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="usage.subscription"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="usage.router"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 font-mono" x-text="usage.ipAddress"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm text-gray-700" x-text="formatBytes(usage.download)"></span>
                                    <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-blue-500 transition-all duration-300" :style="`width: ${Math.min((usage.download / usage.maxUsage) * 100, 100)}%`"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm text-gray-700" x-text="formatBytes(usage.upload)"></span>
                                    <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-green-500 transition-all duration-300" :style="`width: ${Math.min((usage.upload / usage.maxUsage) * 100, 100)}%`"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatBytes(usage.total)"></span>
                                    <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full transition-all duration-300"
                                             :class="usage.total > usage.quota * 0.8 ? 'bg-red-500' : (usage.total > usage.quota * 0.5 ? 'bg-yellow-500' : 'bg-green-500')"
                                             :style="`width: ${Math.min((usage.total / usage.quota) * 100, 100)}%`"></div>
                                    </div>
                                    <span class="text-xs text-gray-500" x-text="Math.round((usage.total / usage.quota) * 100) + '% of quota'"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="usage.sessionTime"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-500" x-text="usage.lastActivity"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span class="font-medium" x-text="pagination.from"></span> to <span class="font-medium" x-text="pagination.to"></span> of <span class="font-medium" x-text="pagination.total"></span> results
            </div>
            <div class="flex items-center gap-2">
                <button @click="previousPage()" :disabled="pagination.currentPage === 1" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <template x-for="page in totalPages" :key="page">
                    <button @click="goToPage(page)" class="px-3 py-2 text-sm font-medium rounded-lg"
                            :class="page === pagination.currentPage ? 'bg-blue-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'"
                            x-text="page"></button>
                </template>
                <button @click="nextPage()" :disabled="pagination.currentPage === totalPages" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Top Users Section -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top 10 Customers by Usage</h3>
            <p class="text-sm text-gray-500">Highest data consumers this period</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usage Bar</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(user, index) in topUsers" :key="user.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                                     :class="index === 0 ? 'bg-yellow-100 text-yellow-700' : (index === 1 ? 'bg-gray-100 text-gray-700' : (index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-600'))"
                                     x-text="index + 1"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="user.customer"></span>
                                    <span class="text-xs text-gray-500" x-text="user.customerId"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900" x-text="formatBytes(user.usage)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="user.plan"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="user.router"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-48">
                                    <div class="bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="index < 3 ? 'bg-gradient-to-r from-blue-500 to-purple-500' : 'bg-blue-500'"
                                             :style="`width: ${(user.usage / topUsers[0].usage) * 100}%`"></div>
                                    </div>
                                </div>
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
function dataUsage() {
    return {
        stats: {
            totalToday: 1543944038,
            totalMonth: 41231686050,
            activeUsers: 312,
            topUserUsage: 365072220,
            topUserName: 'John Smith',
            avgUsagePerUser: 132238333
        },
        filters: {
            dateRange: 'month',
            router: '',
            customer: '',
            subscription: '',
            usageType: ''
        },
        routerOptions: [
            { value: 'r1', label: 'Router-Main-01' },
            { value: 'r2', label: 'Router-Downtown-02' },
            { value: 'r3', label: 'Router-West-03' },
            { value: 'r4', label: 'Router-East-04' },
            { value: 'r5', label: 'Router-North-05' }
        ],
        customerOptions: [
            { value: 'c1', label: 'Acme Corporation' },
            { value: 'c2', label: 'Tech Solutions Inc' },
            { value: 'c3', label: 'Global Services LLC' }
        ],
        subscriptionOptions: [
            { value: 's1', label: 'Enterprise 100Mbps' },
            { value: 's2', label: 'Business 50Mbps' },
            { value: 's3', label: 'Home 20Mbps' }
        ],
        usageData: [],
        pagination: {
            currentPage: 1,
            perPage: 15,
            from: 1,
            to: 15,
            total: 0
        },

        init() {
            this.generateUsageData();
        },

        get filteredUsage() {
            let filtered = this.usageData;

            if (this.filters.router) {
                filtered = filtered.filter(u => u.routerId === this.filters.router);
            }
            if (this.filters.customer) {
                filtered = filtered.filter(u => u.customerId === this.filters.customer);
            }
            if (this.filters.subscription) {
                filtered = filtered.filter(u => u.subscriptionId === this.filters.subscription);
            }

            this.pagination.total = filtered.length;
            const start = (this.pagination.currentPage - 1) * this.pagination.perPage;
            const end = start + this.pagination.perPage;
            this.pagination.from = filtered.length > 0 ? start + 1 : 0;
            this.pagination.to = Math.min(end, filtered.length);

            return filtered.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.pagination.total / this.pagination.perPage);
        },

        get topUsers() {
            return [...this.usageData].sort((a, b) => b.total - a.total).slice(0, 10);
        },

        generateUsageData() {
            const customers = ['John Smith', 'Emma Wilson', 'Michael Brown', 'Sarah Davis', 'James Johnson', 'Emily Taylor', 'David Martinez', 'Lisa Anderson', 'Robert Garcia', 'Maria Rodriguez'];
            const subscriptions = ['Enterprise 100Mbps', 'Business 50Mbps', 'Business 30Mbps', 'Home 20Mbps', 'Home 10Mbps'];
            const routers = ['Router-Main-01', 'Router-Downtown-02', 'Router-West-03', 'Router-East-04', 'Router-North-05'];

            for (let i = 0; i < 50; i++) {
                const download = Math.floor(Math.random() * 500000000000) + 1000000000;
                const upload = Math.floor(Math.random() * 100000000000) + 500000000;
                const total = download + upload;
                const quota = Math.floor(Math.random() * 500000000000) + 50000000000;

                this.usageData.push({
                    id: i + 1,
                    customer: customers[i % customers.length],
                    customerId: `CUST-${String(i + 1).padStart(4, '0')}`,
                    subscription: subscriptions[i % subscriptions.length],
                    subscriptionId: `SUB-${String((i % 5) + 1).padStart(3, '0')}`,
                    router: routers[i % routers.length],
                    routerId: `r${String((i % 5) + 1)}`,
                    ipAddress: `192.168.${Math.floor(i / 255)}.${i % 255}`,
                    download: download,
                    upload: upload,
                    total: total,
                    maxUsage: 500000000000,
                    quota: quota,
                    sessionTime: `${Math.floor(Math.random() * 720)}h ${Math.floor(Math.random() * 60)}m`,
                    lastActivity: this.getRandomDate()
                });
            }
        },

        getRandomDate() {
            const now = new Date();
            const past = new Date(now - Math.random() * 24 * 60 * 60 * 1000);
            return past.toLocaleString();
        },

        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        hasActiveFilters() {
            return this.filters.router || this.filters.customer || this.filters.subscription || this.filters.usageType;
        },

        clearFilters() {
            this.filters = {
                dateRange: 'month',
                router: '',
                customer: '',
                subscription: '',
                usageType: ''
            };
            this.pagination.currentPage = 1;
        },

        exportCSV() {
            alert('Exporting CSV with current filters...');
        },

        refreshData() {
            this.usageData = [];
            this.generateUsageData();
        },

        previousPage() {
            if (this.pagination.currentPage > 1) {
                this.pagination.currentPage--;
            }
        },

        nextPage() {
            if (this.pagination.currentPage < this.totalPages) {
                this.pagination.currentPage++;
            }
        },

        goToPage(page) {
            this.pagination.currentPage = page;
        }
    };
}
</script>
@endscripts
@endsection
