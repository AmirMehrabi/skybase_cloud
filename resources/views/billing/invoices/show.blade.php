@extends('layouts.admin')

@section('title', 'Invoice Details')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="invoiceShow()" x-cloak>
    <!-- Header Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900" x-text="invoice.invoice_number"></h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border"
                          :class="getInvoiceStatusClass(invoice.status)"
                          x-text="invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)"></span>
                </div>
                <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                    <span x-text="invoice.customer_name"></span>
                    <span>•</span>
                    <span x-text="invoice.subscription_code"></span>
                    <span>•</span>
                    <span x-text="invoice.billing_period"></span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a :href="'/billing/invoices/' + invoice.id + '/edit'" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button @click="openPaymentModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Record Payment
                </button>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Send Invoice
                </button>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                        More
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50" style="display: none;">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Download PDF</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Send Reminder</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Duplicate</a>
                        <hr class="my-1">
                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Cancel Invoice</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Invoice Summary -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Issue Date</span>
                            <span class="text-sm font-medium text-gray-900" x-text="invoice.issue_date"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Due Date</span>
                            <span class="text-sm font-medium text-gray-900" x-text="invoice.due_date"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Billing Period</span>
                            <span class="text-sm font-medium text-gray-900" x-text="invoice.billing_period"></span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Subtotal</span>
                            <span class="text-sm font-medium text-gray-900" x-text="formatCurrency(invoice.subtotal)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Tax</span>
                            <span class="text-sm font-medium text-gray-900" x-text="formatCurrency(invoice.tax)"></span>
                        </div>
                        <div class="flex justify-between" x-show="invoice.discount > 0">
                            <span class="text-sm text-gray-500">Discount</span>
                            <span class="text-sm font-medium text-green-600" x-text="'-' + formatCurrency(invoice.discount)"></span>
                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between">
                            <span class="text-sm font-semibold text-gray-900">Total</span>
                            <span class="text-sm font-bold text-gray-900" x-text="formatCurrency(invoice.total)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Paid</span>
                            <span class="text-sm font-medium text-green-600" x-text="formatCurrency(invoice.paid_amount)"></span>
                        </div>
                        <hr class="border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-semibold text-gray-900">Balance Due</span>
                            <span class="text-lg font-bold"
                                  :class="invoice.balance_due > 0 ? 'text-red-600' : 'text-green-600'"
                                  x-text="formatCurrency(invoice.balance_due)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Line Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                                <th class="pb-3 text-center text-xs font-semibold text-gray-500 uppercase">Qty</th>
                                <th class="pb-3 text-right text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
                                <th class="pb-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in invoice.items" :key="index">
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 text-sm text-gray-500" x-text="index + 1"></td>
                                    <td class="py-3 text-sm font-medium text-gray-900" x-text="item.description"></td>
                                    <td class="py-3 text-sm text-gray-700 text-center" x-text="item.quantity"></td>
                                    <td class="py-3 text-sm text-gray-700 text-right" x-text="formatCurrency(item.unit_price)"></td>
                                    <td class="py-3 text-sm font-semibold text-gray-900 text-right" x-text="formatCurrency(item.total)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Payment History</h3>
                    <span class="text-sm text-gray-500" x-text="payments.length + ' payments'"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full" x-show="payments.length > 0">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase">Payment Ref</th>
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase">Method</th>
                                <th class="pb-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="payment in payments" :key="payment.id">
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 text-sm font-medium text-blue-600" x-text="payment.payment_reference"></td>
                                    <td class="py-3 text-sm text-gray-700" x-text="payment.date"></td>
                                    <td class="py-3 text-sm text-gray-700">
                                        <span class="capitalize" x-text="payment.method.replace('_', ' ')"></span>
                                    </td>
                                    <td class="py-3 text-sm font-semibold text-green-600 text-right" x-text="formatCurrency(payment.amount)"></td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700" x-text="payment.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="payments.length === 0" class="py-8 text-center">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">No payments recorded yet</p>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Log</h3>
                <div class="space-y-4">
                    <template x-for="(activity, index) in activities" :key="index">
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                     :class="activity.iconColor">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-html="activity.icon"></svg>
                                </div>
                                <div x-show="index < activities.length - 1" class="w-0.5 flex-1 bg-gray-200 my-1"></div>
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="text-sm font-medium text-gray-900" x-text="activity.title"></p>
                                <p class="text-xs text-gray-500" x-text="activity.description"></p>
                                <p class="text-xs text-gray-400 mt-1" x-text="activity.time"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
            <!-- Customer Info -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Customer</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">
                            <span x-text="invoice.customer_name.charAt(0)"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900" x-text="invoice.customer_name"></p>
                            <p class="text-xs text-gray-500" x-text="invoice.subscription_code"></p>
                        </div>
                    </div>
                    <a href="#" class="block text-sm text-blue-600 hover:text-blue-700">View Customer →</a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Days Overdue</span>
                        <span class="text-sm font-semibold"
                              :class="invoice.days_overdue > 0 ? 'text-red-600' : 'text-gray-900'"
                              x-text="invoice.days_overdue + ' days'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Payment Progress</span>
                        <span class="text-sm font-semibold text-gray-900" x-text="Math.round((invoice.paid_amount / invoice.total) * 100) + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all"
                             :style="'width: ' + (invoice.paid_amount / invoice.total) * 100 + '%'"></div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Notes</h3>
                <div class="text-sm text-gray-700" x-text="invoice.notes || 'No notes added.'"></div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="openPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4" @click.away="openPaymentModal = false">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Record Payment</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Balance Due</label>
                        <input type="text" :value="formatCurrency(invoice.balance_due)" disabled class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-500 sm:text-sm py-2 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Amount</label>
                        <input type="number" x-model="paymentForm.amount" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select x-model="paymentForm.method" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                            <option value="online">Online Payment</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Payment Date</label>
                        <input type="date" x-model="paymentForm.date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button @click="openPaymentModal = false" class="px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
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
<script src="{{ asset('js/billing/invoices-show.js') }}"></script>
@endscripts
@endsection
