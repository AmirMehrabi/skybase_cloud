@extends('layouts.admin')

@section('title', 'Usage Reports')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="usageReports()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Usage Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Historical usage analytics and trends</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="generateReport()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Generate Report
            </button>
            <button @click="exportPDF()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Export PDF
            </button>
            <button @click="exportCSV()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Report Filters</h3>
            <button x-show="hasActiveFilters()" @click="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-medium" style="display: none;">
                Clear All
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date Range</label>
                <select x-model="filters.dateRange" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="today">Today</option>
                    <option value="week">Last 7 Days</option>
                    <option value="month" selected>This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                    <option value="custom">Custom Range</option>
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
                <label class="block text-xs font-medium text-gray-700 mb-1">Plan</label>
                <select x-model="filters.plan" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="">All Plans</option>
                    <template x-for="plan in planOptions" :key="plan.value">
                        <option :value="plan.value" x-text="plan.label"></option>
                    </template>
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
                <label class="block text-xs font-medium text-gray-700 mb-1">Group By</label>
                <select x-model="filters.groupBy" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                    <option value="day">Day</option>
                    <option value="week">Week</option>
                    <option value="month" selected>Month</option>
                    <option value="quarter">Quarter</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Usage -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Usage</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(summary.totalUsage)"></p>
                    <p class="text-xs text-green-600 mt-2">+12.5% from last period</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Average Usage -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Average Usage</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(summary.avgUsage)"></p>
                    <p class="text-xs text-gray-500 mt-2">Per customer</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Peak Usage -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Peak Usage</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatBytes(summary.peakUsage)"></p>
                    <p class="text-xs text-gray-500 mt-2" x-text="summary.peakDate"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="summary.activeUsers"></p>
                    <p class="text-xs text-gray-500 mt-2">This period</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Report Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Usage by Period</h3>
            <p class="text-sm text-gray-500">Detailed consumption data grouped by time period</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Download</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Upload</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sessions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="record in filteredRecords" :key="record.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900" x-text="record.period"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="record.customer"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-blue-600" x-text="formatBytes(record.download)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-green-600" x-text="formatBytes(record.upload)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900" x-text="formatBytes(record.total)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="record.sessions"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Visual Report Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Usage Trend</h3>
                <p class="text-sm text-gray-500">Historical data visualization</p>
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
        <div class="h-80 relative bg-gray-50 rounded-xl overflow-hidden p-4">
            <!-- Grid Lines -->
            <div class="absolute inset-0 flex flex-col justify-between px-4 py-12">
                <div class="border-b border-gray-200 border-dashed"></div>
                <div class="border-b border-gray-200 border-dashed"></div>
                <div class="border-b border-gray-200 border-dashed"></div>
                <div class="border-b border-gray-200 border-dashed"></div>
            </div>

            <!-- Y-axis labels -->
            <div class="absolute left-0 top-0 bottom-0 flex flex-col justify-between py-12 pl-2 text-xs text-gray-400">
                <span>5 TB</span>
                <span>3.75 TB</span>
                <span>2.5 TB</span>
                <span>1.25 TB</span>
                <span>0</span>
            </div>

            <!-- Chart -->
            <div class="absolute inset-0 flex items-end justify-between px-8 pb-4 pt-12 pl-16">
                <template x-for="(point, index) in chartData" :key="index">
                    <div class="flex flex-col items-center gap-1 flex-1 max-w-12">
                        <div class="w-full flex flex-col-reverse gap-0.5">
                            <div class="w-full bg-blue-500 rounded-t transition-all duration-300 hover:bg-blue-600"
                                 :style="`height: ${(point.download / 5000000000000) * 100}%`"
                                 :title="`Download: ${formatBytes(point.download)}`"></div>
                            <div class="w-full bg-green-500 rounded-b transition-all duration-300 hover:bg-green-600"
                                 :style="`height: ${(point.upload / 5000000000000) * 100}%`"
                                 :title="`Upload: ${formatBytes(point.upload)}`"></div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2" x-text="point.period"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@scripts
<script>
function usageReports() {
    return {
        filters: {
            dateRange: 'month',
            customer: '',
            plan: '',
            router: '',
            groupBy: 'month'
        },
        customerOptions: [
            { value: 'all', label: 'All Customers' },
            { value: 'enterprise', label: 'Enterprise Customers' },
            { value: 'business', label: 'Business Customers' },
            { value: 'residential', label: 'Residential Customers' }
        ],
        planOptions: [
            { value: 'all', label: 'All Plans' },
            { value: 'enterprise', label: 'Enterprise Plans' },
            { value: 'business', label: 'Business Plans' },
            { value: 'home', label: 'Home Plans' }
        ],
        routerOptions: [
            { value: 'all', label: 'All Routers' },
            { value: 'main', label: 'Main Data Center' },
            { value: 'downtown', label: 'Downtown' },
            { value: 'west', label: 'West Tower' }
        ],
        summary: {
            totalUsage: 5497558138880,
            avgUsage: 18325193796,
            peakUsage: 879609302220,
            peakDate: 'Jan 15, 2026',
            activeUsers: 287
        },
        records: [],
        chartData: [],

        init() {
            this.generateRecords();
            this.generateChartData();
        },

        get filteredRecords() {
            return this.records;
        },

        generateRecords() {
            const customers = ['Acme Corp', 'Tech Solutions', 'Global Services', 'Metro Bank', 'City Hospital', 'University District', 'Retail Chain Co', 'Manufacturing Inc'];
            const periods = ['Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026', 'May 2026', 'Jun 2026'];

            let id = 1;
            periods.forEach(period => {
                customers.forEach(customer => {
                    const download = Math.floor(Math.random() * 500000000000) + 50000000000;
                    const upload = Math.floor(Math.random() * 100000000000) + 10000000000;

                    this.records.push({
                        id: id++,
                        period: period,
                        customer: customer,
                        download: download,
                        upload: upload,
                        total: download + upload,
                        sessions: Math.floor(Math.random() * 500) + 50
                    });
                });
            });
        },

        generateChartData() {
            const periods = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            for (let i = 0; i < 6; i++) {
                this.chartData.push({
                    period: periods[i],
                    download: Math.floor(Math.random() * 4000000000000) + 500000000000,
                    upload: Math.floor(Math.random() * 800000000000) + 100000000000
                });
            }
        },

        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        hasActiveFilters() {
            return this.filters.customer || this.filters.plan || this.filters.router;
        },

        clearFilters() {
            this.filters.customer = '';
            this.filters.plan = '';
            this.filters.router = '';
        },

        generateReport() {
            alert('Generating report with current filters...');
        },

        exportPDF() {
            alert('Exporting report as PDF...');
        },

        exportCSV() {
            alert('Exporting report data as CSV...');
        }
    };
}
</script>
@endscripts
@endsection
