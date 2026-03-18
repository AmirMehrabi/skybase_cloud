@extends('layouts.admin')

@section('title', 'Edit Router')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-24" x-data="routerEdit({{ $router }}, {{ $router->toArray() }})" x-cloak>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Router</h1>
            <p class="text-sm text-gray-500 mt-1" x-text="`Updating ${router.name}`"></p>
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
            <x-input.text
                id="name"
                name="name"
                label="Router Name"
                placeholder="e.g., Core-Router-1"
                :required="true"
                xModel="form.name"
                :error="errors.name"
            />

            <x-input.select
                id="vendor"
                name="vendor"
                label="Vendor"
                :required="true"
                xModel="form.vendor"
                :options="[
                    'Mikrotik' => 'Mikrotik',
                    'Cisco' => 'Cisco',
                    'Juniper' => 'Juniper',
                    'Huawei' => 'Huawei',
                ]"
                placeholder="Select vendor"
                :error="errors.vendor"
            />

            <x-input.text
                id="model"
                name="model"
                label="Model"
                placeholder="e.g., CCR1036-12G-4S"
                xModel="form.model"
                :error="errors.model"
            />

            <x-input.text
                id="location"
                name="location"
                label="Location"
                placeholder="e.g., Data Center"
                xModel="form.location"
                :error="errors.location"
            />

            <x-input.text
                id="site"
                name="site"
                label="Site"
                placeholder="e.g., Main Site"
                xModel="form.site"
                :error="errors.site"
            />
        </div>
    </div>

    <!-- Section 2: Connection Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-input.text
                id="ip_address"
                name="ip_address"
                label="IP Address"
                placeholder="192.168.1.1"
                :required="true"
                xModel="form.ip_address"
                :error="errors.ip_address"
            />

            <x-input.number
                id="api_port"
                name="api_port"
                label="API Port"
                :required="true"
                :min="1"
                :max="65535"
                xModel="form.api_port"
                :error="errors.api_port"
            />

            <x-input.number
                id="ssh_port"
                name="ssh_port"
                label="SSH Port"
                :required="true"
                :min="1"
                :max="65535"
                xModel="form.ssh_port"
                :error="errors.ssh_port"
            />

            <x-input.text
                id="api_username"
                name="api_username"
                label="API Username"
                placeholder="admin"
                xModel="form.api_username"
                :error="errors.api_username"
            />

            <x-input.password
                id="api_password"
                name="api_password"
                label="API Password"
                placeholder="Leave blank to keep current"
                xModel="form.api_password"
                :error="errors.api_password"
            />
        </div>
    </div>

    <!-- Section 3: Advanced Settings -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Advanced Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-input.number
                id="timeout"
                name="timeout"
                label="Timeout (seconds)"
                :min="1"
                :max="300"
                help="Connection timeout in seconds"
                xModel="form.timeout"
                :error="errors.timeout"
            />

            <div class="flex items-center pt-6">
                <x-input.checkbox
                    id="enable_monitoring"
                    name="enable_monitoring"
                    label="Enable Monitoring"
                    xModel="form.enable_monitoring"
                />
            </div>

            <div class="flex items-center pt-6">
                <x-input.checkbox
                    id="enable_provisioning"
                    name="enable_provisioning"
                    label="Enable Provisioning"
                    xModel="form.enable_provisioning"
                />
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
        <div class="flex items-center justify-end gap-3">
            <a :href="`{{ route('routers.show', '') }}/${router.id}`" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
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
<script>
function routerEdit(router, routerData) {
    return {
        router: router,
        form: {
            name: routerData.name || '',
            model: routerData.model || '',
            vendor: routerData.vendor || '',
            ip_address: routerData.ip_address || '',
            api_port: routerData.api_port || 8728,
            ssh_port: routerData.ssh_port || 22,
            api_username: routerData.api_username || '',
            api_password: '',
            location: routerData.location || '',
            site: routerData.site || '',
            timeout: routerData.timeout || 30,
            enable_monitoring: routerData.enable_monitoring ?? true,
            enable_provisioning: routerData.enable_provisioning ?? true,
        },
        errors: {},
        saving: false,

        async save() {
            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch(`{{ route('routers.index') }}/${this.router.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.href = `{{ route('routers.show', '') }}/${this.router.id}`;
                } else if (response.status === 422) {
                    this.errors = data.errors || {};
                    this.showValidationErrors();
                } else {
                    alert(data.message || 'Error updating router. Please try again.');
                }
            } catch (error) {
                console.error('Error updating router:', error);
                alert('Error updating router. Please try again.');
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
