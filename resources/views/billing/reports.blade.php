@extends('layouts.admin')

@section('title', 'Billing Reports')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="billingReports()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Billing Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Financial analytics and performance metrics</p>
        </div>
        <div class="flex items-center gap-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span x-text="selectedPeriod"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" style="display: none;">
                    <a href="#" @click.prevent="selectedPeriod = 'This Month'; open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">This Month</a>
                    <a href="#" @click.prevent="selectedPeriod = 'Last Month'; open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Last Month</a>
                    <a href="#" @click.prevent="selectedPeriod = 'This Quarter'; open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">This Quarter</a>
                    <a href="#" @click.prevent="selectedPeriod = 'This Year'; open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">This Year</a>
                    <hr class="my-1">
                    <a href="#" @click.prevent="selectedPeriod = 'Custom'; open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Custom Range</a>
                </div>
            </div>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export Report
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="formatCurrency(revenue.total)"></p>
                    <p class="text-xs text-green-600 mt-1">
                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        +15.3% vs last period
                    </p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Collected</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="formatCurrency(revenue.collected)"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="revenue.collectionRate + '% collection rate'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Outstanding</p>
                    <p class="text-2xl font-bold text-red-600 mt-2" x-text="formatCurrency(revenue.outstanding)"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="revenue.overdueInvoices + ' overdue invoices'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Avg Collection</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="revenue.avgCollectionDays + ' days'"></p>
                    <p class="text-xs text-gray-500 mt-1">Days to collect</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Revenue Trend Chart -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Revenue Trend</h3>
                    <p class="text-sm text-gray-500">Monthly revenue over time</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-sm text-gray-600">Revenue</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-sm text-gray-600">Collected</span>
                    </div>
                </div>
            </div>

            <!-- Chart Placeholder -->
            <div class="h-64 relative">
                <div class="absolute inset-0 flex items-end justify-between gap-2 px-4">
                    <template x-for="(month, index) in revenueChart" :key="index">
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full flex flex-col-reverse gap-1">
                                <div class="w-full bg-blue-500 rounded-t transition-all duration-500" :style="'height: ' + (month.revenue / 200) + '%'" :title="'Revenue: ' + formatCurrency(month.revenue)"></div>
                                <div class="w-full bg-green-500 rounded-b transition-all duration-500" :style="'height: ' + (month.collected / 200) + '%'" :title="'Collected: ' + formatCurrency(month.collected)"></div>
                            </div>
                            <span class="text-xs text-gray-500 rotate-45 origin-bottom-left mt-2" x-text="month.month"></span>
                        </div>
                    </template>
                </div>

                <!-- Y-axis labels -->
                <div class="absolute left-0 top-0 bottom-8 flex flex-col justify-between text-xs text-gray-400 -ml-1">
                    <span>$200k</span>
                    <span>$150k</span>
                    <span>$100k</span>
                    <span>$50k</span>
                    <span>$0</span>
                </div>
            </div>
        </div>

        <!-- Payment Methods Chart -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Payment Methods</h3>
                    <p class="text-sm text-gray-500">Distribution by payment type</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <template x-for="method in paymentMethods" :key="method.name">
                    <div class="p-4 border rounded-xl hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="method.bgColor">
                                <span x-html="method.icon"></span>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900" x-text="method.name"></p>
                                <p class="text-xs text-gray-500" x-text="method.count + ' transactions'"></p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500">Percentage</span>
                                <span class="text-xs font-semibold text-gray-900" x-text="method.percentage + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all" :class="method.barColor" :style="'width: ' + method.percentage + '%'"></div>
                            </div>
                            <p class="text-sm font-semibold mt-2" x-text="formatCurrency(method.amount)"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Top Customers by Revenue -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Top Customers</h3>
                    <p class="text-sm text-gray-500">By revenue this period</p>
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="(customer, index) in topCustomers" :key="customer.id">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm"
                             :class="index < 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'"
                             x-text="index + 1"></div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-medium text-gray-900" x-text="customer.name"></p>
                                <p class="text-sm font-semibold text-gray-900" x-text="formatCurrency(customer.revenue)"></p>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="bg-blue-600 h-1.5 rounded-full transition-all" :style="'width: ' + (customer.revenue / topCustomers[0].revenue * 100) + '%'"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Invoice Aging Report -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Invoice Aging</h3>
                    <p class="text-sm text-gray-500">Outstanding by aging period</p>
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="aging in agingReport" :key="aging.period">
                    <div class="p-4 border rounded-xl">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" :class="aging.dotColor"></span>
                                <span class="text-sm font-medium text-gray-900" x-text="aging.period"></span>
                            </div>
                            <span class="text-sm font-semibold" :class="aging.textColor" x-text="formatCurrency(aging.amount)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500" x-text="aging.count + ' invoices'"></span>
                            <div class="w-1/2 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all" :class="aging.barColor" :style="'width: ' + (aging.amount / agingReport[0].amount * 100) + '%'"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Total Outstanding</span>
                    <span class="text-lg font-bold text-red-600" x-text="formatCurrency(totalAging)"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/billing/reports.js') }}"></script>
@endscripts
@endsection
