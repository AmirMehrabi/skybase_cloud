@extends('layouts.admin')

@section('title', 'Add New Router')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .input-error {
        border-color: #ef4444 !important;
    }
    .error-message {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="routerCreate()" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add New Router</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new router to your network infrastructure</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Section 1: Basic Information -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Router Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    x-model="form.name"
                    :class="{'input-error': errors.name}"
                    placeholder="e.g., Core-Router-1"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.name" x-text="errors.name" class="error-message"></p>
            </div>

            <div>
                <label for="vendor" class="block text-sm font-medium text-gray-700 mb-2">
                    Vendor <span class="text-red-500">*</span>
                </label>
                <select
                    id="vendor"
                    name="vendor"
                    x-model="form.vendor"
                    :class="{'input-error': errors.vendor}"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white"
                >
                    <option value="">Select vendor</option>
                    <option value="Mikrotik">Mikrotik</option>
                    <option value="Cisco">Cisco</option>
                    <option value="Juniper">Juniper</option>
                    <option value="Huawei">Huawei</option>
                </select>
                <p x-show="errors.vendor" x-text="errors.vendor" class="error-message"></p>
            </div>

            <div>
                <label for="model" class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                <input
                    type="text"
                    id="model"
                    name="model"
                    x-model="form.model"
                    :class="{'input-error': errors.model}"
                    placeholder="e.g., CCR1036-12G-4S"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.model" x-text="errors.model" class="error-message"></p>
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    x-model="form.location"
                    :class="{'input-error': errors.location}"
                    placeholder="e.g., Data Center"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.location" x-text="errors.location" class="error-message"></p>
            </div>

            <div>
                <label for="site" class="block text-sm font-medium text-gray-700 mb-2">Site</label>
                <input
                    type="text"
                    id="site"
                    name="site"
                    x-model="form.site"
                    :class="{'input-error': errors.site}"
                    placeholder="e.g., Main Site"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.site" x-text="errors.site" class="error-message"></p>
            </div>
        </div>
    </div>

    <!-- Section 2: Connection Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-2">
                    IP Address <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="ip_address"
                    name="ip_address"
                    x-model="form.ip_address"
                    :class="{'input-error': errors.ip_address}"
                    placeholder="192.168.1.1"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.ip_address" x-text="errors.ip_address" class="error-message"></p>
            </div>

            <div>
                <label for="api_port" class="block text-sm font-medium text-gray-700 mb-2">
                    API Port <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    id="api_port"
                    name="api_port"
                    x-model="form.api_port"
                    :class="{'input-error': errors.api_port}"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.api_port" x-text="errors.api_port" class="error-message"></p>
            </div>

            <div>
                <label for="ssh_port" class="block text-sm font-medium text-gray-700 mb-2">
                    SSH Port <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    id="ssh_port"
                    name="ssh_port"
                    x-model="form.ssh_port"
                    :class="{'input-error': errors.ssh_port}"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.ssh_port" x-text="errors.ssh_port" class="error-message"></p>
            </div>

            <div>
                <label for="api_username" class="block text-sm font-medium text-gray-700 mb-2">API Username</label>
                <input
                    type="text"
                    id="api_username"
                    name="api_username"
                    x-model="form.api_username"
                    :class="{'input-error': errors.api_username}"
                    placeholder="admin"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.api_username" x-text="errors.api_username" class="error-message"></p>
            </div>

            <div>
                <label for="api_password" class="block text-sm font-medium text-gray-700 mb-2">API Password</label>
                <input
                    type="password"
                    id="api_password"
                    name="api_password"
                    x-model="form.api_password"
                    :class="{'input-error': errors.api_password}"
                    placeholder="Enter API password"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p x-show="errors.api_password" x-text="errors.api_password" class="error-message"></p>
            </div>
        </div>
    </div>

    <!-- Section 3: Advanced Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Advanced Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label for="timeout" class="block text-sm font-medium text-gray-700 mb-2">Timeout (seconds)</label>
                <input
                    type="number"
                    id="timeout"
                    name="timeout"
                    x-model="form.timeout"
                    :class="{'input-error': errors.timeout}"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border"
                >
                <p class="text-xs text-gray-500 mt-1">Connection timeout in seconds</p>
                <p x-show="errors.timeout" x-text="errors.timeout" class="error-message"></p>
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="enable_monitoring"
                        value="1"
                        x-model="form.enable_monitoring"
                        class="h-4 w-4 text-blue-600 rounded border-gray-300"
                    >
                    <span class="text-sm text-gray-700">Enable Monitoring</span>
                </label>
            </div>

            <div class="flex items-center pt-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="enable_provisioning"
                        value="1"
                        x-model="form.enable_provisioning"
                        class="h-4 w-4 text-blue-600 rounded border-gray-300"
                    >
                    <span class="text-sm text-gray-700">Enable Provisioning</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('routers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                Cancel
            </a>
            <button @click="save" :disabled="saving" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="saving ? 'Saving...' : 'Save Router'"></span>
            </button>
        </div>
    </div>
</div>

@scripts
<script>
function routerCreate() {
    return {
        form: {
            name: '',
            model: '',
            vendor: '',
            ip_address: '',
            api_port: 8728,
            ssh_port: 22,
            api_username: '',
            api_password: '',
            location: '',
            site: '',
            timeout: 30,
            enable_monitoring: true,
            enable_provisioning: true,
        },
        errors: {},
        saving: false,

        async save() {
            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch('{{ route('routers.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.href = '{{ route('routers.index') }}';
                } else if (response.status === 422) {
                    this.errors = data.errors || {};
                    this.showValidationErrors();
                } else {
                    alert(data.message || 'Error creating router. Please try again.');
                }
            } catch (error) {
                console.error('Error creating router:', error);
                alert('Error creating router. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        showValidationErrors() {
            const firstError = Object.keys(this.errors)[0];
            if (firstError) {
                const element = document.getElementById(firstError);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    element.focus();
                }
            }
        }
    };
}
</script>
@endscripts
@endsection
