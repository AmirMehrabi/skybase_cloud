@extends('layouts.admin')

@section('title', "Customer Profile")

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="customerShow({{ $id }})" x-cloak>
    <!-- Profile Header Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white" x-show="customer">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <!-- Avatar -->
                <div class="w-20 h-20 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl font-bold" x-text="customer?.name?.charAt(0)?.toUpperCase() || 'U'"></div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold" x-text="customer?.name || 'Loading...'"></h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                              :class="getStatusBadgeClass(customer?.status)"
                              x-text="customer?.status ? customer.status.charAt(0).toUpperCase() + customer.status.slice(1) : ''"></span>
                    </div>
                    <p class="text-blue-100 text-sm mt-1" x-text="customer?.customer_code || ''"></p>
                    <p class="text-blue-100 text-sm" x-text="(customer?.email || '') + ' • ' + (customer?.mobile || '')"></p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a :href="`/customers/${customer?.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Create Invoice
                </button>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Suspend
                </button>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-white/20" x-show="customer">
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Current Plan</p>
                <p class="text-lg font-semibold mt-1" x-text="customer?.plan || ''"></p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Balance</p>
                <p class="text-lg font-semibold mt-1" :class="customer?.balance < 0 ? 'text-green-300' : 'text-red-300'" x-text="formatBalance(customer?.balance || 0)"></p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">IP Address</p>
                <p class="text-lg font-semibold mt-1 font-mono" x-text="customer?.ip_address || ''"></p>
            </div>
            <div>
                <p class="text-blue-100 text-xs uppercase tracking-wider">Billing Cycle</p>
                <p class="text-lg font-semibold mt-1 capitalize" x-text="customer?.billing_cycle || ''"></p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div x-show="customer">
        <nav class="border-b border-gray-200">
            <div class="flex space-x-8 overflow-x-auto">
                <template x-for="(label, key) in tabs" :key="key">
                    <button @click="activeTab = key"
                            :class="activeTab === key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                            x-text="label"></button>
                </template>
            </div>
        </nav>

        <!-- Tab Content -->
        <div class="mt-6">
            <!-- Overview Tab -->
            <div x-show="activeTab === 'overview'">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Basic Info Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Customer Code</span>
                                <span class="font-medium text-gray-900" x-text="customer?.customer_code || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Type</span>
                                <span class="font-medium text-gray-900 capitalize" x-text="customer?.type || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">National ID</span>
                                <span class="font-medium text-gray-900" x-text="customer?.national_id || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status</span>
                                <span class="font-medium text-gray-900 capitalize" x-text="customer?.status || ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500 block">Email</span>
                                <span class="font-medium text-gray-900" x-text="customer?.email || ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Phone</span>
                                <span class="font-medium text-gray-900" x-text="customer?.phone || ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Mobile</span>
                                <span class="font-medium text-gray-900" x-text="customer?.mobile || ''"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">WhatsApp</span>
                                <span class="font-medium text-gray-900" x-text="customer?.whatsapp || ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Address Info Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <span class="text-gray-500 block">Address</span>
                                <span class="font-medium text-gray-900" x-text="customer?.address || ''"></span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="text-gray-500 block">City</span>
                                    <span class="font-medium text-gray-900" x-text="customer?.city || ''"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">State</span>
                                    <span class="font-medium text-gray-900" x-text="customer?.state || ''"></span>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Country</span>
                                <span class="font-medium text-gray-900" x-text="customer?.country || ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Summary</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Balance</span>
                                <span class="font-semibold" :class="customer?.balance < 0 ? 'text-green-600' : 'text-red-600'" x-text="formatBalance(customer?.balance || 0)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Credit Limit</span>
                                <span class="font-medium text-gray-900" x-text="'$' + (customer?.credit_limit || 0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Billing Cycle</span>
                                <span class="font-medium text-gray-900 capitalize" x-text="customer?.billing_cycle || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tax Exempt</span>
                                <span class="font-medium text-gray-900" x-text="customer?.tax_exempt ? 'Yes' : 'No'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Network Assignment Card -->
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Network Assignment</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Plan</span>
                                <span class="font-medium text-gray-900" x-text="customer?.plan || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Site</span>
                                <span class="font-medium text-gray-900" x-text="customer?.site || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Router</span>
                                <span class="font-medium text-gray-900" x-text="customer?.router || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">IP Address</span>
                                <span class="font-medium text-gray-900 font-mono" x-text="customer?.ip_address || ''"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">MAC Address</span>
                                <span class="font-medium text-gray-900 font-mono" x-text="customer?.mac_address || ''"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services Tab -->
            <div x-show="activeTab === 'services'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Service ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Router</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Activated</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">#SRV-1</td>
                                    <td class="px-6 py-4 text-sm text-gray-700" x-text="customer?.plan || ''"></td>
                                    <td class="px-6 py-4 text-sm text-gray-700" x-text="customer?.router || ''"></td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-700" x-text="customer?.ip_address || ''"></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                              :class="getStatusBadgeClass(customer?.status)"
                                              x-text="customer?.status ? customer.status.charAt(0).toUpperCase() + customer.status.slice(1) : ''"></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500" x-text="customer?.activated_at || ''"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Invoices Tab -->
            <div x-show="activeTab === 'invoices'" style="display: none;">
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="invoice in invoices" :key="invoice.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm font-medium text-blue-600" x-text="invoice.number"></td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900" x-text="'$' + invoice.amount.toFixed(2)"></td>
                                        <td class="px-6 py-4 text-sm text-gray-500" x-text="invoice.due_date"></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                  :class="getStatusBadgeClass(invoice.status)"
                                                  x-text="invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Usage Tab -->
            <div x-show="activeTab === 'usage'" style="display: none;">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Data Usage</h3>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-xl">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 mt-2">Data usage chart placeholder</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Session History</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Session 1</p>
                                    <p class="text-xs text-gray-500">Feb 17, 2024 - 10:30 AM</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">2h 45m</p>
                                    <p class="text-xs text-gray-500">1.2 GB</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Session 2</p>
                                    <p class="text-xs text-gray-500">Feb 16, 2024 - 2:15 PM</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">4h 20m</p>
                                    <p class="text-xs text-gray-500">3.8 GB</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Session 3</p>
                                    <p class="text-xs text-gray-500">Feb 15, 2024 - 9:00 AM</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">5h 10m</p>
                                    <p class="text-xs text-gray-500">4.5 GB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tickets Tab -->
            <div x-show="activeTab === 'tickets'" style="display: none;">
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm text-center">
                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mt-4">No Support Tickets</h3>
                    <p class="text-sm text-gray-500 mt-1">This customer has no open or closed tickets.</p>
                </div>
            </div>

            <!-- Activity Log Tab -->
            <div x-show="activeTab === 'activity'" style="display: none;">
                <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Timeline</h3>
                    <div class="space-y-6">
                        <template x-for="(activity, index) in activityLog" :key="index">
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="w-0.5 flex-1 bg-gray-200 mt-2" x-show="index < activityLog.length - 1"></div>
                                </div>
                                <div class="flex-1 pb-6">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900" x-text="activity.action"></p>
                                        <p class="text-xs text-gray-500" x-text="activity.time"></p>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1" x-text="activity.description"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/customers/data.js') }}"></script>
<script src="{{ asset('js/customers/show.js') }}"></script>
@endscripts
@endsection
