@extends('layouts.admin')

@section('title', 'Invoices')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="invoicesIndex()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and track all customer invoices</p>
        </div>
        <div class="flex items-center gap-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" style="display: none;">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as CSV</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as PDF</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as Excel</a>
                </div>
            </div>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Generate Recurring
            </button>
            <a href="{{ route('billing.invoices.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Invoice
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Filters</h3>
            <button x-show="hasActiveFilters" @click="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-medium" style="display: none;">
                Clear All
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <div class="xl:col-span-2">
                <input type="text" x-model="search" placeholder="Search invoice #, customer..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <select x-model="status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Statuses</option>
                <template x-for="option in filterOptions.statuses" :key="option.value">
                    <option :value="option.value" x-text="option.label"></option>
                </template>
            </select>

            <input type="date" x-model="dateFrom" placeholder="From Date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">

            <input type="date" x-model="dateTo" placeholder="To Date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">

            <input type="text" x-model="customer" placeholder="Customer name" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs font-medium text-gray-500">Total Invoices</p>
            <p class="text-xl font-bold text-gray-900 mt-1" x-text="filteredInvoices.length"></p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs font-medium text-gray-500">Total Amount</p>
            <p class="text-xl font-bold text-gray-900 mt-1" x-text="formatCurrency(totalAmount)"></p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs font-medium text-gray-500">Paid Amount</p>
            <p class="text-xl font-bold text-green-600 mt-1" x-text="formatCurrency(paidAmount)"></p>
        </div>
        <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
            <p class="text-xs font-medium text-gray-500">Outstanding</p>
            <p class="text-xl font-bold text-red-600 mt-1" x-text="formatCurrency(outstandingAmount)"></p>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Invoice #</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issue Date</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="invoice in paginatedInvoices" :key="invoice.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <a :href="'/billing/invoices/' + invoice.id" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="invoice.invoice_number"></a>
                                    <span class="text-xs text-gray-500" x-text="invoice.subscription_code"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900" x-text="invoice.customer_name"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="invoice.issue_date"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="invoice.due_date"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(invoice.total)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-green-600" x-text="formatCurrency(invoice.paid_amount)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold"
                                      :class="invoice.balance_due > 0 ? 'text-red-600' : 'text-green-600'"
                                      x-text="formatCurrency(invoice.balance_due)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="getInvoiceStatusClass(invoice.status)"
                                      x-text="invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a :href="'/billing/invoices/' + invoice.id" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a :href="'/billing/invoices/' + invoice.id + '/edit'" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button @click="openPaymentModal(invoice)" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Record Payment">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                    <a href="#" class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Download PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open" @click.outside="open = false" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                        <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" style="display: none;">
                                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Send Reminder</a>
                                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Mark as Sent</a>
                                            <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Cancel Invoice</a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="paginatedInvoices.length === 0" style="display: none;">
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">No invoices found</p>
                                <button @click="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                    Clear Filters
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="totalInvoices > perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span x-text="(currentPage - 1) * perPage + 1"></span> to <span x-text="Math.min(currentPage * perPage, totalInvoices)"></span> of <span x-text="totalInvoices"></span> invoices
            </div>
            <div class="flex items-center gap-2">
                <button @click="prevPage()" :disabled="currentPage === 1" class="px-3 py-1 rounded border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                    Previous
                </button>
                <template x-for="page in displayedPages" :key="page">
                    <button @click="goToPage(page)" :class="currentPage === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" class="px-3 py-1 rounded border text-sm min-w-[36px]" x-text="page"></button>
                </template>
                <button @click="nextPage()" :disabled="currentPage === totalPages" class="px-3 py-1 rounded border border-gray-300 text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="paymentModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4" @click.away="paymentModalOpen = false">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Record Payment</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Invoice</label>
                        <input type="text" :value="selectedInvoice?.invoice_number" disabled class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-500 sm:text-sm py-2 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Balance Due</label>
                        <input type="text" :value="formatCurrency(selectedInvoice?.balance_due || 0)" disabled class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-500 sm:text-sm py-2 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Amount</label>
                        <input type="number" x-model="paymentAmount" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select x-model="paymentMethod" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="online">Online Payment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Date</label>
                        <input type="date" x-model="paymentDate" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button @click="paymentModalOpen = false" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="recordPayment()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    Record Payment
                </button>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/billing/invoices-index.js') }}"></script>
@endscripts
@endsection
