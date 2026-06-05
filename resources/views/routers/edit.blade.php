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
                    label="Legacy Site Name"
                    name="site"
                    placeholder="e.g., Main Site"
                    :value="old('site', $router->site)"
                    :error="$errors->first('site')"
                />

                <x-ui.input.select
                    label="Managed Site"
                    name="site_id"
                    :options="$sites->mapWithKeys(fn ($site) => [$site->id => $site->name . ' (' . $site->code . ')'])->toArray()"
                    :value="old('site_id', $router->site_id)"
                    placeholder="Select managed site"
                    :error="$errors->first('site_id')"
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

                <x-ui.input.text
                    type="number"
                    label="CoA Port"
                    name="coa_port"
                    :value="old('coa_port', $router->coa_port ?? 1700)"
                    :error="$errors->first('coa_port')"
                    hint="Default: 1700"
                />

                <x-ui.input.text
                    type="password"
                    label="CoA Secret"
                    name="coa_secret"
                    placeholder="Leave blank to keep current"
                    :error="$errors->first('coa_secret')"
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

        <!-- Section 4: NetFlow Settings -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">NetFlow Settings</h3>
                    <p class="text-sm text-gray-500 mt-1">Available for MikroTik routers using RouterOS Traffic Flow.</p>
                </div>
                <x-ui.input.checkbox
                    label="Enable NetFlow"
                    name="netflow_enabled"
                    :checked="old('netflow_enabled', $router->netflow_enabled)"
                    :error="$errors->first('netflow_enabled')"
                />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    label="Collector Host"
                    name="netflow_collector_host"
                    placeholder="collector.skybase.local"
                    :value="old('netflow_collector_host', $router->netflow_collector_host ?? config('netflow.collector_host'))"
                    :error="$errors->first('netflow_collector_host')"
                />

                <x-ui.input.text
                    type="number"
                    label="Collector Port"
                    name="netflow_collector_port"
                    :value="old('netflow_collector_port', $router->netflow_collector_port ?? config('netflow.collector_port'))"
                    :error="$errors->first('netflow_collector_port')"
                    hint="Default: 2055"
                />

                <x-ui.input.select
                    label="Version"
                    name="netflow_version"
                    :options="[9 => 'NetFlow v9', 5 => 'NetFlow v5']"
                    :value="old('netflow_version', $router->netflow_version ?? 9)"
                    :error="$errors->first('netflow_version')"
                />

                <x-ui.input.text
                    label="Interfaces"
                    name="netflow_interfaces"
                    placeholder="all"
                    :value="old('netflow_interfaces', $router->netflow_interfaces ?? 'all')"
                    :error="$errors->first('netflow_interfaces')"
                />

                <x-ui.input.text
                    type="number"
                    label="Sampling Interval"
                    name="netflow_sampling_interval"
                    :value="old('netflow_sampling_interval', $router->netflow_sampling_interval ?? 1)"
                    :error="$errors->first('netflow_sampling_interval')"
                    hint="1 means every packet"
                />
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
