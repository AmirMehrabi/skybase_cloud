@extends('layouts.admin')

@section('title', 'Billing Dashboard')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="billingDashboard()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Billing Overview</h1>
            <p class="text-sm text-gray-500 mt-1">Revenue, invoices and payment monitoring</p>
        </div>
        <div class="flex items-center gap-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    This Month
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" style="display: none;">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">This Month</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Last Month</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">This Quarter</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">This Year</a>
                </div>
            </div>
            <a href="{{ route('billing.invoices.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Invoice
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
        <!-- Total Revenue -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="formatCurrency(stats.revenue)"></p>
                    <p class="text-xs text-green-600 mt-1">
                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        +12.5% from last month
                    </p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Outstanding Balance -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Outstanding</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="formatCurrency(stats.outstanding)"></p>
                    <p class="text-xs text-gray-500 mt-1" x-text="stats.pendingInvoices + ' pending invoices'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Overdue Amount -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Overdue</p>
                    <p class="text-2xl font-bold text-red-600 mt-2" x-text="formatCurrency(stats.overdue)"></p>
                    <p class="text-xs text-red-600 mt-1" x-text="stats.overdueInvoices + ' invoices'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Paid Invoices -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Paid Invoices</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="stats.paidInvoices"></p>
                    <p class="text-xs text-green-600 mt-1">This month</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Unpaid Invoices -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Unpaid</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="stats.unpaidInvoices"></p>
                    <p class="text-xs text-gray-500 mt-1">Awaiting payment</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Customers with Balance -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Customers w/ Balance</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2" x-text="stats.customersWithBalance"></p>
                    <p class="text-xs text-gray-500 mt-1">Active accounts</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Alert -->
    <div x-show="stats.overdueInvoices > 0" class="bg-red-50 border border-red-200 rounded-2xl p-6" style="display: none;">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-red-900">
                    <span x-text="stats.overdueInvoices"></span> invoices are overdue
                </h3>
                <p class="text-sm text-red-700 mt-1">
                    Total overdue amount: <span class="font-semibold" x-text="formatCurrency(stats.overdue)"></span>
                </p>
            </div>
            <a href="{{ route('billing.invoices.index') }}?status=overdue" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
                View Overdue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- Revenue Chart Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Revenue Trend</h3>
                <p class="text-sm text-gray-500">Monthly revenue for the last 6 months</p>
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

    <!-- Recent Invoices Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Recent Invoices</h3>
                <p class="text-sm text-gray-500">Latest invoices generated in the system</p>
            </div>
            <a href="{{ route('billing.invoices.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                View All →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="invoice in recentInvoices" :key="invoice.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <a :href="'/billing/invoices/' + invoice.id" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="invoice.invoice_number"></a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="invoice.customer_name"></span>
                                    <span class="text-xs text-gray-500" x-text="invoice.subscription_code"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(invoice.total)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="invoice.due_date"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="getInvoiceStatusClass(invoice.status)"
                                      x-text="invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-semibold"
                                      :class="invoice.balance_due > 0 ? 'text-red-600' : 'text-green-600'"
                                      x-text="formatCurrency(invoice.balance_due)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/billing/dashboard.js') }}"></script>
@endscripts
@endsection
