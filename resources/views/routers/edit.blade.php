@extends('layouts.admin')

@section('title', 'Edit Router')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="routerEdit" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Router</h1>
            <p class="text-sm text-gray-500 mt-1" x-text="`Updating ${router.name}`"></p>
        </div>
    </div>

    <!-- Section 1: Basic Information -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Router Name <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.name" placeholder="e.g., Core-Router-1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Vendor <span class="text-red-500">*</span></label>
                <select x-model="form.vendor" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                    <option value="">Select vendor</option>
                    <option value="Mikrotik">Mikrotik</option>
                    <option value="Cisco">Cisco</option>
                    <option value="Juniper">Juniper</option>
                    <option value="Huawei">Huawei</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                <input type="text" x-model="form.model" placeholder="e.g., CCR1036-12G-4S" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <input type="text" x-model="form.location" placeholder="e.g., Data Center" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Site</label>
                <input type="text" x-model="form.site" placeholder="e.g., Main Site" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
        </div>
    </div>

    <!-- Section 2: Connection Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">IP Address <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.ip_address" placeholder="192.168.1.1" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Port <span class="text-red-500">*</span></label>
                <input type="number" x-model="form.api_port" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SSH Port <span class="text-red-500">*</span></label>
                <input type="number" x-model="form.ssh_port" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Username</label>
                <input type="text" x-model="form.api_username" placeholder="admin" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Password</label>
                <input type="password" x-model="form.api_password" placeholder="Leave blank to keep current" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
        </div>
    </div>

    <!-- Section 3: Advanced Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Advanced Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Timeout (seconds)</label>
                <input type="number" x-model="form.timeout" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
                <p class="text-xs text-gray-500 mt-1">Connection timeout in seconds (default: 30)</p>
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.enable_monitoring" class="h-4 w-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Enable Monitoring</span>
                </label>
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="form.enable_provisioning" class="h-4 w-4 text-blue-600 rounded border-gray-300">
                    <span class="text-sm text-gray-700">Enable Provisioning</span>
                </label>
            </div>
        </div>

        <!-- Test Connection Button -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <button @click="testConnection" :disabled="testingConnection" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="!testingConnection" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <svg x-show="testingConnection" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="testingConnection ? 'Testing Connection...' : 'Test Connection'"></span>
            </button>

            <!-- Connection Result -->
            <div x-show="connectionResult" x-transition class="mt-4" style="display: none;">
                <div :class="connectionResult.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'" class="px-4 py-3 rounded-lg border">
                    <div class="flex items-center gap-2">
                        <svg x-show="connectionResult.success" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <svg x-show="!connectionResult.success" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium" x-text="connectionResult.message"></span>
                    </div>
                    <div x-show="connectionResult.details" class="mt-2 text-xs" x-text="connectionResult.details"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a :href="`/routers/${router.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Cancel
            </a>
            <button @click="save" :disabled="saving" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="saving ? 'Updating...' : 'Update Router'"></span>
            </button>
        </div>
    </div>
</div>

@scripts
<script src="{{ asset('js/routers/edit.js') }}"></script>
@endscripts
@endsection
