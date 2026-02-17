@extends('layouts.admin')

@section('title', 'Payment Details')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="paymentShow()" x-cloak>
    <!-- Header Section -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900" x-text="payment.payment_reference"></h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border"
                          :class="getPaymentStatusClass(payment.status)"
                          x-text="payment.status.charAt(0).toUpperCase() + payment.status.slice(1)"></span>
                </div>
                <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                    <span x-text="payment.customer_name"></span>
                    <span>•</span>
                    <a :href="'/billing/invoices/' + payment.invoice_id" class="text-blue-600 hover:text-blue-700" x-text="payment.invoice_number"></a>
                    <span>•</span>
                    <span x-text="payment.date"></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Receipt
                </button>
                <a :href="'/billing/invoices/' + payment.invoice_id" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    View Invoice
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Payment Summary -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Payment Reference</span>
                            <span class="text-sm font-medium text-gray-900" x-text="payment.payment_reference"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Payment Date</span>
                            <span class="text-sm font-medium text-gray-900" x-text="payment.date"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Payment Method</span>
                            <span class="text-sm font-medium text-gray-900 capitalize" x-text="payment.method.replace('_', ' ')"></span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Amount Paid</span>
                            <span class="text-sm font-bold text-green-600" x-text="formatCurrency(payment.amount)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Invoice Total</span>
                            <span class="text-sm font-medium text-gray-900" x-text="formatCurrency(payment.invoice_total)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Remaining Balance</span>
                            <span class="text-sm font-semibold"
                                  :class="payment.remaining_balance > 0 ? 'text-red-600' : 'text-green-600'"
                                  x-text="formatCurrency(payment.remaining_balance)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Allocation Details -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Allocation Details</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <a :href="'/billing/invoices/' + payment.invoice_id" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="payment.invoice_number"></a>
                                <p class="text-xs text-gray-500" x-text="payment.invoice_description"></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900" x-text="formatCurrency(payment.amount)"></p>
                            <p class="text-xs text-gray-500">Full Payment</p>
                        </div>
                    </div>

                    <!-- Payment Breakdown -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Payment Breakdown</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Principal</span>
                                <span class="font-medium text-gray-900" x-text="formatCurrency(payment.amount * 0.91)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tax (10%)</span>
                                <span class="font-medium text-gray-900" x-text="formatCurrency(payment.amount * 0.09)"></span>
                            </div>
                            <hr class="border-gray-200">
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold text-gray-900">Total Paid</span>
                                <span class="font-bold text-green-600" x-text="formatCurrency(payment.amount)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Log -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Log</h3>
                <div class="space-y-4">
                    <template x-for="(log, index) in transactionLogs" :key="index">
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                     :class="log.iconColor">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-html="log.icon"></svg>
                                </div>
                                <div x-show="index < transactionLogs.length - 1" class="w-0.5 flex-1 bg-gray-200 my-1"></div>
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="text-sm font-medium text-gray-900" x-text="log.title"></p>
                                <p class="text-xs text-gray-500" x-text="log.description"></p>
                                <p class="text-xs text-gray-400 mt-1" x-text="log.time"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-1 space-y-6">
            <!-- Amount Card -->
            <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-sm font-medium text-blue-100">Amount Received</p>
                <p class="text-3xl font-bold mt-2" x-text="formatCurrency(payment.amount)"></p>
                <div class="mt-4 flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white/20" x-text="payment.method.replace('_', ' ')"></span>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Customer</h3>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">
                            <span x-text="payment.customer_name.charAt(0)"></span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900" x-text="payment.customer_name"></p>
                            <p class="text-xs text-gray-500">Customer ID: <span x-text="payment.customer_id"></span></p>
                        </div>
                    </div>
                    <a href="#" class="block text-sm text-blue-600 hover:text-blue-700">View Customer Profile →</a>
                </div>
            </div>

            <!-- Invoice Info -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Applied To Invoice</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <a :href="'/billing/invoices/' + payment.invoice_id" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="payment.invoice_number"></a>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="payment.invoice_date"></p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Paid</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Invoice Amount</span>
                            <span class="font-medium text-gray-900" x-text="formatCurrency(payment.invoice_total)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Applied</span>
                            <span class="font-medium text-green-600" x-text="formatCurrency(payment.amount)"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Balance</span>
                            <span class="font-medium"
                                  :class="payment.remaining_balance > 0 ? 'text-red-600' : 'text-green-600'"
                                  x-text="formatCurrency(payment.remaining_balance)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Actions</h3>
                <div class="space-y-2">
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Send Receipt
                    </button>
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refund Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/billing/payments-show.js') }}"></script>
@endscripts
@endsection
