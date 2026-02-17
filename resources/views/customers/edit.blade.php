@extends('layouts.admin')

@section('title', 'Edit Customer')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="customerEdit({{ $id }})" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between" x-show="customer">
        <div class="flex items-center gap-4">
            <a href="/customers" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Customer</h1>
                <p class="text-sm text-gray-500 mt-1" x-text="customer?.customer_code || ''"></p>
            </div>
        </div>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
              :class="getStatusBadgeClass(customer?.status)"
              x-text="customer?.status ? customer.status.charAt(0).toUpperCase() + customer.status.slice(1) : ''"></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6" x-show="customer">
        <!-- Main Form (3 columns) -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Section 1: Basic Information -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Type</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="form.customerType" value="individual" class="h-4 w-4 text-blue-600">
                                <span class="text-sm text-gray-700">Individual</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="form.customerType" value="business" class="h-4 w-4 text-blue-600">
                                <span class="text-sm text-gray-700">Business</span>
                            </label>
                        </div>
                    </div>

                    <template x-if="form.customerType === 'individual'">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                            <input type="text" x-model="form.firstName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                            <input type="text" x-model="form.lastName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                        </div>
                    </template>

                    <template x-if="form.customerType === 'business'">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                            <input type="text" x-model="form.companyName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                        </div>
                    </template>
                </div>
            </div>

            <!-- Section 2: Contact Information -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" x-model="form.email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="text" x-model="form.phone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mobile</label>
                        <input type="text" x-model="form.mobile" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp</label>
                        <input type="text" x-model="form.whatsapp" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                </div>
            </div>

            <!-- Section 3: Service Assignment -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Service Assignment</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plan</label>
                        <select x-model="form.plan" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                            <option value="Fiber 50 Mbps">Fiber 50 Mbps</option>
                            <option value="Fiber 100 Mbps">Fiber 100 Mbps</option>
                            <option value="Fiber 200 Mbps">Fiber 200 Mbps</option>
                            <option value="Fiber 500 Mbps">Fiber 500 Mbps</option>
                            <option value="Fiber 1 Gbps">Fiber 1 Gbps</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site</label>
                        <select x-model="form.site" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                            <option value="Downtown Hub">Downtown Hub</option>
                            <option value="Business Park">Business Park</option>
                            <option value="North Tower">North Tower</option>
                            <option value="South Station">South Station</option>
                            <option value="West Station">West Station</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Router</label>
                        <select x-model="form.router" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                            <option value="Mikrotik-01">Mikrotik-01</option>
                            <option value="Mikrotik-02">Mikrotik-02</option>
                            <option value="Cisco-01">Cisco-01</option>
                            <option value="Cisco-02">Cisco-02</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PPPoE Username</label>
                        <input type="text" x-model="form.pppoeUsername" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">PPPoE Password</label>
                        <input type="password" x-model="form.pppoePassword" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                </div>
            </div>

            <!-- Section 4: Financial Settings -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Balance</label>
                        <input type="number" step="0.01" x-model="form.balance" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                        <input type="number" step="0.01" x-model="form.creditLimit" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                    </div>
                </div>
            </div>

            <!-- Section 5: Status -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select x-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Sidebar (1 column) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <button @click="suspendService" class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl hover:bg-yellow-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center group-hover:bg-yellow-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Suspend Service</p>
                            <p class="text-xs text-gray-500">Temporarily disable</p>
                        </div>
                    </button>
                    <button @click="resetPPPoE" class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-xl hover:bg-blue-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Reset PPPoE</p>
                            <p class="text-xs text-gray-500">Generate new password</p>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a :href="`/customers/${customerId}`" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Cancel
            </a>
            <button @click="update" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Changes
            </button>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/customers/data.js') }}"></script>
<script src="{{ asset('js/customers/edit.js') }}"></script>
@endscripts
@endsection
