@extends('layouts.admin')

@section('title', 'Add New Router')

@section('content')
<div class="space-y-6 pb-24">
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

    <form method="POST" action="{{ route('routers.store') }}">
        @csrf

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
                    :value="old('name')"
                />

                <div>
                    <label for="vendor" class="block text-sm font-medium text-gray-700 mb-2">
                        Vendor <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="vendor"
                        name="vendor"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white @error('vendor') border-red-500 @enderror"
                    >
                        <option value="">Select vendor</option>
                        <option value="Mikrotik" {{ old('vendor') === 'Mikrotik' ? 'selected' : '' }}>Mikrotik</option>
                        <option value="Cisco" {{ old('vendor') === 'Cisco' ? 'selected' : '' }}>Cisco</option>
                        <option value="Juniper" {{ old('vendor') === 'Juniper' ? 'selected' : '' }}>Juniper</option>
                        <option value="Huawei" {{ old('vendor') === 'Huawei' ? 'selected' : '' }}>Huawei</option>
                    </select>
                    @error('vendor')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-input.text
                    id="model"
                    name="model"
                    label="Model"
                    placeholder="e.g., CCR1036-12G-4S"
                    :value="old('model')"
                />

                <x-input.text
                    id="location"
                    name="location"
                    label="Location"
                    placeholder="e.g., Data Center"
                    :value="old('location')"
                />

                <x-input.text
                    id="site"
                    name="site"
                    label="Site"
                    placeholder="e.g., Main Site"
                    :value="old('site')"
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
                    :value="old('ip_address')"
                />

                <x-input.number
                    id="api_port"
                    name="api_port"
                    label="API Port"
                    :required="true"
                    :value="old('api_port', 8728)"
                    :min="1"
                    :max="65535"
                />

                <x-input.number
                    id="ssh_port"
                    name="ssh_port"
                    label="SSH Port"
                    :required="true"
                    :value="old('ssh_port', 22)"
                    :min="1"
                    :max="65535"
                />

                <x-input.text
                    id="api_username"
                    name="api_username"
                    label="API Username"
                    placeholder="admin"
                    :value="old('api_username')"
                />

                <x-input.password
                    id="api_password"
                    name="api_password"
                    label="API Password"
                    placeholder="Enter API password"
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
                    :value="old('timeout', 30)"
                    :min="1"
                    :max="300"
                    help="Connection timeout in seconds"
                />

                <div class="flex items-center pt-6">
                    <x-input.checkbox
                        id="enable_monitoring"
                        name="enable_monitoring"
                        label="Enable Monitoring"
                        :checked="old('enable_monitoring', true)"
                    />
                </div>

                <div class="flex items-center pt-6">
                    <x-input.checkbox
                        id="enable_provisioning"
                        name="enable_provisioning"
                        label="Enable Provisioning"
                        :checked="old('enable_provisioning', true)"
                    />
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('routers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Router
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
