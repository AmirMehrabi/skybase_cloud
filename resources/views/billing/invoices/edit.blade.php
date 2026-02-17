@extends('layouts.admin')

@section('title', 'Edit Invoice')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="editInvoice()" x-cloak>
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Invoice</h1>
            <p class="text-sm text-gray-500 mt-1" x-text="'Editing ' + form.invoice_number"></p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('billing.invoices.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Cancel
            </a>
            <button x-show="form.status === 'draft'" @click="saveAsDraft()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700">
                Save as Draft
            </button>
            <button @click="updateInvoice()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Update Invoice
            </button>
        </div>
    </div>

    <!-- Paid Invoice Warning -->
    <div x-show="form.status === 'paid'" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4" style="display: none;">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <p class="text-sm text-yellow-800">This invoice has been paid. Editing may affect accounting records.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Main Form Section -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm" :class="{ 'opacity-75': form.status === 'paid' }">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                        <input type="text" x-model="form.customer_name" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subscription</label>
                        <select x-model="form.subscription_code" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white" :disabled="form.status === 'paid'">
                            <option value="">Select Subscription</option>
                            <template x-for="sub in subscriptions" :key="sub.code">
                                <option :value="sub.code" x-text="sub.name + ' (' + sub.code + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                        <input type="text" x-model="form.invoice_number" class="block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-500 sm:text-sm py-2.5 px-3 border" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label>
                        <input type="date" x-model="form.issue_date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input type="date" x-model="form.due_date" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Billing Period</label>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="month" x-model="form.billing_period_from" placeholder="From" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                            <input type="month" x-model="form.billing_period_to" placeholder="To" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm" :class="{ 'opacity-75': form.status === 'paid' }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Invoice Items</h3>
                    <button x-show="form.status !== 'paid'" @click="addLineItem()" class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Line Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase w-1/2">Description</th>
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
                                <th class="pb-3 text-left text-xs font-semibold text-gray-500 uppercase w-32">Unit Price</th>
                                <th class="pb-3 text-right text-xs font-semibold text-gray-500 uppercase w-32">Total</th>
                                <th class="pb-3 text-center text-xs font-semibold text-gray-500 uppercase w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in form.items" :key="index">
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 pr-4">
                                        <input type="text" x-model="item.description" placeholder="Item description" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border" :disabled="form.status === 'paid'">
                                    </td>
                                    <td class="py-3 px-2">
                                        <input type="number" x-model="item.quantity" min="1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border" :disabled="form.status === 'paid'">
                                    </td>
                                    <td class="py-3 px-2">
                                        <input type="number" x-model="item.unit_price" step="0.01" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border" :disabled="form.status === 'paid'">
                                    </td>
                                    <td class="py-3 pl-2 text-right">
                                        <span class="text-sm font-medium text-gray-900" x-text="formatCurrency(item.total)"></span>
                                    </td>
                                    <td class="py-3 text-center">
                                        <button x-show="form.status !== 'paid'" @click="removeLineItem(index)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Discounts & Taxes -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm" :class="{ 'opacity-75': form.status === 'paid' }">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Discounts & Taxes</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                        <input type="number" x-model="form.discount_percent" min="0" max="100" step="0.1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax (%)</label>
                        <input type="number" x-model="form.tax_percent" min="0" max="100" step="0.1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Type</label>
                        <select x-model="form.tax_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white" :disabled="form.status === 'paid'">
                            <option value="exclusive">Exclusive</option>
                            <option value="inclusive">Inclusive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea x-model="form.notes" rows="3" placeholder="Add any notes or payment instructions..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border" :disabled="form.status === 'paid'"></textarea>
                </div>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="xl:col-span-1">
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm sticky top-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Summary</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Subtotal</span>
                        <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(form.subtotal)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Discount</span>
                        <span class="text-sm font-semibold text-green-600" x-text="'-' + formatCurrency(form.discount_amount)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Tax</span>
                        <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(form.tax_amount)"></span>
                    </div>
                    <div class="flex justify-between items-center py-3 bg-blue-50 rounded-lg px-3 -mx-3">
                        <span class="text-base font-semibold text-gray-900">Total</span>
                        <span class="text-xl font-bold text-blue-600" x-text="formatCurrency(form.total)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Paid</span>
                        <span class="text-sm font-semibold text-green-600" x-text="formatCurrency(form.paid_amount)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-t border-gray-200">
                        <span class="text-sm font-semibold text-gray-900">Balance Due</span>
                        <span class="text-lg font-bold"
                              :class="form.balance_due > 0 ? 'text-red-600' : 'text-green-600'"
                              x-text="formatCurrency(form.balance_due)"></span>
                    </div>
                </div>

                <!-- Status -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Status</label>
                    <select x-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                        <option value="draft">Draft</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="mt-6 pt-6 border-t border-gray-200 space-y-3">
                    <button @click="updateInvoice()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Invoice
                    </button>
                    <a :href="'/billing/invoices/' + form.id" class="block w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 text-center">
                        View Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/billing/invoices-edit.js') }}"></script>
@endscripts
@endsection
