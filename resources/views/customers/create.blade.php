@extends('layouts.admin')

@section('title', 'Create New Customer')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="customerCreate" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Customer</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new subscriber to your ISP network</p>
        </div>
    </div>

    <!-- Section 1: Basic Information -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-3">
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.firstName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.lastName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                </div>
            </template>

            <template x-if="form.customerType === 'business'">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.companyName" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                </div>
            </template>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Code</label>
                <input type="text" :value="generatedCustomerCode" readonly class="block w-full rounded-lg border-gray-300 bg-gray-50 sm:text-sm py-2.5 px-3 border">
            </div>

            <template x-if="form.customerType === 'individual'">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">National ID / SSN</label>
                    <input type="text" x-model="form.nationalId" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                </div>
            </template>
        </div>
    </div>

    <!-- Section 2: Contact Information -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                <input type="email" x-model="form.email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                <input type="text" x-model="form.phone" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mobile Number <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.mobile" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp Number</label>
                <input type="text" x-model="form.whatsapp" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
        </div>
    </div>

    <!-- Section 3: Address Information -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Address Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.addressLine1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                <input type="text" x-model="form.addressLine2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">City <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.city" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">State / Province</label>
                <input type="text" x-model="form.state" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                <input type="text" x-model="form.postalCode" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                <select x-model="form.country" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="United States">United States</option>
                    <option value="Canada">Canada</option>
                    <option value="United Kingdom">United Kingdom</option>
                    <option value="Germany">Germany</option>
                    <option value="France">France</option>
                    <option value="Australia">Australia</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Section 4: Service Assignment -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Service Assignment</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Service Plan <span class="text-red-500">*</span></label>
                <select x-model="form.plan" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="">Select a plan</option>
                    <option value="Fiber 50 Mbps">Fiber 50 Mbps - $29.99/mo</option>
                    <option value="Fiber 100 Mbps">Fiber 100 Mbps - $49.99/mo</option>
                    <option value="Fiber 200 Mbps">Fiber 200 Mbps - $69.99/mo</option>
                    <option value="Fiber 500 Mbps">Fiber 500 Mbps - $99.99/mo</option>
                    <option value="Fiber 1 Gbps">Fiber 1 Gbps - $149.99/mo</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site / Location <span class="text-red-500">*</span></label>
                <select x-model="form.site" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="">Select a site</option>
                    <option value="Downtown Hub">Downtown Hub</option>
                    <option value="Business Park">Business Park</option>
                    <option value="North Tower">North Tower</option>
                    <option value="South Station">South Station</option>
                    <option value="West Station">West Station</option>
                    <option value="East Center">East Center</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Router / NAS <span class="text-red-500">*</span></label>
                <select x-model="form.router" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="">Select a router</option>
                    <option value="Mikrotik-01">Mikrotik-01 (Downtown)</option>
                    <option value="Mikrotik-02">Mikrotik-02 (Business)</option>
                    <option value="Mikrotik-03">Mikrotik-03 (North)</option>
                    <option value="Cisco-01">Cisco-01 (West)</option>
                    <option value="Cisco-02">Cisco-02 (Business)</option>
                    <option value="Cisco-03">Cisco-03 (North)</option>
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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle</label>
                <select x-model="form.billingCycle" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly (3 months)</option>
                    <option value="yearly">Yearly (12 months)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Section 5: Financial Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Initial Balance</label>
                <input type="number" step="0.01" x-model="form.initialBalance" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                <p class="text-xs text-gray-500 mt-1">Positive = debit owed, Negative = credit balance</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Credit Limit</label>
                <input type="number" step="0.01" x-model="form.creditLimit" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                <p class="text-xs text-gray-500 mt-1">Maximum allowed debt amount</p>
            </div>
            <div class="flex items-center pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.taxExempt" class="h-4 w-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Tax Exempt</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Section 6: Status -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Status & Activation</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Initial Status</label>
                <select x-model="form.status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="pending">Pending Activation</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="flex items-center pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.autoActivate" class="h-4 w-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Auto-activate service on save</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a href="/customers" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Cancel
            </a>
            <button @click="saveDraft" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Save Draft
            </button>
            <button @click="save" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Create & Activate
            </button>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/customers/create.js') }}"></script>
@endscripts
@endsection
