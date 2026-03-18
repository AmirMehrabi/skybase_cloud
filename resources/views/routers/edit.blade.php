@extends('layouts.admin')

@section('title', 'Edit Router')

@section('content')
<div class="space-y-6 pb-24">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Router</h1>
            <p class="text-sm text-gray-500 mt-1">Updating {{ $router->name }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('routers.update', $router) }}">
        @csrf
        @method('PUT')

        <!-- Section 1: Basic Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    label="Router Name"
                    name="name"
                    placeholder="e.g., Core-Router-1"
                    :required="true"
                    :value="old('name', $router->name)"
                    :error="$errors->first('name')"
                />

                <x-ui.input.select
                    label="Vendor"
                    name="vendor"
                    :options="[
                        'Mikrotik' => 'Mikrotik',
                        'Cisco' => 'Cisco',
                        'Juniper' => 'Juniper',
                        'Huawei' => 'Huawei',
                    ]"
                    :value="old('vendor', $router->vendor)"
                    placeholder="Select vendor"
                    :required="true"
                    :error="$errors->first('vendor')"
                />

                <x-ui.input.text
                    label="Model"
                    name="model"
                    placeholder="e.g., CCR1036-12G-4S"
                    :value="old('model', $router->model)"
                    :error="$errors->first('model')"
                />

                <x-ui.input.text
                    label="Location"
                    name="location"
                    placeholder="e.g., Data Center"
                    :value="old('location', $router->location)"
                    :error="$errors->first('location')"
                />

                <x-ui.input.text
                    label="Site"
                    name="site"
                    placeholder="e.g., Main Site"
                    :value="old('site', $router->site)"
                    :error="$errors->first('site')"
                />
            </div>
        </div>

        <!-- Section 2: Connection Settings -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Connection Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    type="text"
                    label="IP Address"
                    name="ip_address"
                    placeholder="192.168.1.1"
                    :required="true"
                    :value="old('ip_address', $router->ip_address)"
                    :error="$errors->first('ip_address')"
                />

                <x-ui.input.text
                    type="number"
                    label="API Port"
                    name="api_port"
                    :value="old('api_port', $router->api_port)"
                    :error="$errors->first('api_port')"
                    hint="Default: 8728"
                />

                <x-ui.input.text
                    type="number"
                    label="SSH Port"
                    name="ssh_port"
                    :value="old('ssh_port', $router->ssh_port)"
                    :error="$errors->first('ssh_port')"
                    hint="Default: 22"
                />

                <x-ui.input.text
                    label="API Username"
                    name="api_username"
                    placeholder="admin"
                    :value="old('api_username', $router->api_username)"
                    :error="$errors->first('api_username')"
                />

                <x-ui.input.text
                    type="password"
                    label="API Password"
                    name="api_password"
                    placeholder="Leave blank to keep current"
                    :error="$errors->first('api_password')"
                />
            </div>
        </div>

        <!-- Section 3: Advanced Settings -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Advanced Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    type="number"
                    label="Timeout (seconds)"
                    name="timeout"
                    :value="old('timeout', $router->timeout ?? 30)"
                    :error="$errors->first('timeout')"
                    hint="Connection timeout in seconds"
                />

                <div class="flex items-center pt-6">
                    <x-ui.input.checkbox
                        label="Enable Monitoring"
                        name="enable_monitoring"
                        :checked="old('enable_monitoring', $router->enable_monitoring ?? true)"
                        :error="$errors->first('enable_monitoring')"
                    />
                </div>

                <div class="flex items-center pt-6">
                    <x-ui.input.checkbox
                        label="Enable Provisioning"
                        name="enable_provisioning"
                        :checked="old('enable_provisioning', $router->enable_provisioning ?? true)"
                        :error="$errors->first('enable_provisioning')"
                    />
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('routers.show', $router) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Router
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
