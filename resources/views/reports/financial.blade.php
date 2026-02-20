@extends('layouts.admin')

@section('title', 'Financial Reports')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="financialReports()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Financial Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Revenue analytics and financial performance</p>
        </div>
        <div class="flex items-center gap-3">
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
        <!-- Revenue This Month -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Revenue This Month</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatCurrency(summary.revenueThisMonth)"></p>
                    <div class="flex items-center gap-1 mt-2">
                        <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-xs text-green-600">+12.5%</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Revenue Last Month -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Revenue Last Month</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatCurrency(summary.revenueLastMonth)"></p>
                    <p class="text-xs text-gray-500 mt-2">Previous period</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Outstanding Balance -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Outstanding Balance</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatCurrency(summary.outstandingBalance)"></p>
                    <p class="text-xs text-yellow-600 mt-2" x-text="summary.pendingInvoices + ' pending invoices'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-yellow-50 text-yellow-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Overdue Amount -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Overdue Amount</p>
                    <p class="text-3xl font-bold text-red-600 mt-2" x-text="formatCurrency(summary.overdueAmount)"></p>
                    <p class="text-xs text-red-600 mt-2" x-text="summary.overdueInvoices + ' overdue invoices'"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- ARPU -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">ARPU</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatCurrency(summary.arpu)"></p>
                    <p class="text-xs text-gray-500 mt-2">Avg Revenue Per User</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Monthly Revenue</h3>
            <p class="text-sm text-gray-500">Revenue breakdown by month</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoices Issued</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoices Paid</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenue</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Outstanding</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Collection Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="record in revenueRecords" :key="record.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900" x-text="record.month"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="record.invoicesIssued"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="record.invoicesPaid"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-green-600" x-text="formatCurrency(record.revenue)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-red-600" x-text="formatCurrency(record.outstanding)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-300"
                                             :class="record.collectionRate >= 90 ? 'bg-green-500' : (record.collectionRate >= 75 ? 'bg-yellow-500' : 'bg-red-500')"
                                             :style="`width: ${record.collectionRate}%`"></div>
                                    </div>
                                    <span class="text-sm text-gray-700" x-text="record.collectionRate + '%'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Customers by Revenue -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Top Customers by Revenue</h3>
            <p class="text-sm text-gray-500">Highest revenue generating customers</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rank</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Paid</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Subscription</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenue Trend</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="(customer, index) in topCustomers" :key="customer.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                                     :class="index === 0 ? 'bg-yellow-100 text-yellow-700' : (index === 1 ? 'bg-gray-100 text-gray-700' : (index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-600'))"
                                     x-text="index + 1"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="customer.name"></span>
                                    <span class="text-xs text-gray-500" x-text="customer.company"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(customer.totalPaid)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="customer.activeSubscription ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'"
                                      x-text="customer.activeSubscription ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="customer.plan"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <template x-for="i in 6" :key="i">
                                        <div class="w-2 h-6 rounded-sm"
                                             :class="i <= customer.trendLevel ? 'bg-green-500' : 'bg-gray-200'"></div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Methods Table -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Payment Method Breakdown</h3>
                <p class="text-sm text-gray-500">Revenue by payment method</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Amount</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Count</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="method in paymentMethods" :key="method.id">
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                             :class="method.colorClass">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900" x-text="method.name"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(method.amount)"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700" x-text="method.count"></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-300"
                                                 :class="method.barColor"
                                                 :style="`width: ${method.percentage}%`"></div>
                                        </div>
                                        <span class="text-sm text-gray-700" x-text="method.percentage + '%'"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Revenue Chart Placeholder -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Revenue Trend</h3>
                    <p class="text-sm text-gray-500">6-month revenue overview</p>
                </div>
            </div>

            <div class="h-64 relative">
                <div class="absolute inset-0 flex items-end justify-between gap-2 px-4">
                    <template x-for="(month, index) in revenueChartData" :key="index">
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full bg-gradient-to-t from-blue-600 to-blue-400 rounded-t transition-all duration-500 hover:from-blue-700 hover:to-blue-500"
                                 :style="'height: ' + (month.revenue / 200000) + '%'"
                                 :title="'Revenue: ' + formatCurrency(month.revenue)"></div>
                            <span class="text-xs text-gray-500 rotate-45 origin-bottom-left mt-2" x-text="month.month"></span>
                        </div>
                    </template>
                </div>

                <div class="absolute left-0 top-0 bottom-8 flex flex-col justify-between text-xs text-gray-400 -ml-1">
                    <span>$200k</span>
                    <span>$150k</span>
                    <span>$100k</span>
                    <span>$50k</span>
                    <span>$0</span>
                </div>
            </div>
        </div>
    </div>
</div>

@scripts
<script>
function financialReports() {
    return {
        summary: {
            revenueThisMonth: 185420,
            revenueLastMonth: 164830,
            outstandingBalance: 48750,
            overdueAmount: 12350,
            arpu: 89.50,
            pendingInvoices: 47,
            overdueInvoices: 12
        },
        revenueRecords: [],
        topCustomers: [],
        paymentMethods: [],
        revenueChartData: [],

        init() {
            this.generateRevenueRecords();
            this.generateTopCustomers();
            this.generatePaymentMethods();
            this.generateRevenueChart();
        },

        generateRevenueRecords() {
            const months = ['August 2025', 'September 2025', 'October 2025', 'November 2025', 'December 2025', 'January 2026', 'February 2026'];

            for (let i = 0; i < 7; i++) {
                const issued = Math.floor(Math.random() * 100) + 80;
                const paid = Math.floor(issued * (Math.random() * 0.2 + 0.75));
                const revenue = paid * (Math.random() * 500 + 200);
                const outstanding = (issued - paid) * (Math.random() * 500 + 200);
                const collectionRate = Math.round((paid / issued) * 100);

                this.revenueRecords.push({
                    id: i + 1,
                    month: months[i],
                    invoicesIssued: issued,
                    invoicesPaid: paid,
                    revenue: Math.floor(revenue),
                    outstanding: Math.floor(outstanding),
                    collectionRate: collectionRate
                });
            }
        },

        generateTopCustomers() {
            const customers = [
                { name: 'John Smith', company: 'Acme Corporation', plan: 'Enterprise 100Mbps' },
                { name: 'Sarah Johnson', company: 'Tech Solutions Inc', plan: 'Enterprise 50Mbps' },
                { name: 'Michael Chen', company: 'Global Services LLC', plan: 'Business 30Mbps' },
                { name: 'Emily Davis', company: 'Metro Bank', plan: 'Enterprise 100Mbps' },
                { name: 'Robert Wilson', company: 'City Hospital', plan: 'Enterprise 200Mbps' },
                { name: 'Lisa Anderson', company: 'University District', plan: 'Enterprise 500Mbps' },
                { name: 'David Martinez', company: 'Retail Chain Co', plan: 'Business 50Mbps' },
                { name: 'Jennifer Taylor', company: 'Manufacturing Inc', plan: 'Enterprise 100Mbps' }
            ];

            this.topCustomers = customers.map((c, i) => ({
                id: i + 1,
                name: c.name,
                company: c.company,
                plan: c.plan,
                totalPaid: Math.floor(Math.random() * 50000) + 10000,
                activeSubscription: Math.random() > 0.2,
                trendLevel: Math.floor(Math.random() * 6) + 1
            })).sort((a, b) => b.totalPaid - a.totalPaid);
        },

        generatePaymentMethods() {
            this.paymentMethods = [
                { id: 1, name: 'Bank Transfer', amount: 82450, count: 145, percentage: 52, colorClass: 'bg-blue-100 text-blue-600', barColor: 'bg-blue-500' },
                { id: 2, name: 'Credit Card', amount: 48230, count: 287, percentage: 30, colorClass: 'bg-green-100 text-green-600', barColor: 'bg-green-500' },
                { id: 3, name: 'Online Payment', amount: 19200, count: 156, percentage: 12, colorClass: 'bg-purple-100 text-purple-600', barColor: 'bg-purple-500' },
                { id: 4, name: 'Cash', amount: 9540, count: 42, percentage: 6, colorClass: 'bg-orange-100 text-orange-600', barColor: 'bg-orange-500' }
            ];
        },

        generateRevenueChart() {
            const months = ['Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb'];
            for (let i = 0; i < 6; i++) {
                this.revenueChartData.push({
                    month: months[i],
                    revenue: Math.floor(Math.random() * 100000) + 80000
                });
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },

        exportPDF() {
            alert('Exporting financial report as PDF...');
        },

        exportCSV() {
            alert('Exporting financial data as CSV...');
        }
    };
}
</script>
@endscripts
@endsection
