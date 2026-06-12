@extends('layouts.admin')

@section('title', 'Add New Access Point')

@section('content')
<div class="space-y-6 pb-24">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add New Access Point</h1>
            <p class="text-sm text-gray-500 mt-1">Deploy a new wireless access point to your network</p>
        </div>
    </div>

    <form method="POST" action="{{ route('access-points.store') }}">
        @csrf

        <!-- Section 1: Basic Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    label="Access Point Name"
                    name="name"
                    placeholder="e.g., Tower-A-AP1"
                    :required="true"
                    :value="old('name')"
                    :error="$errors->first('name')"
                />

                <x-ui.input.select
                    label="Vendor"
                    name="vendor"
                    :options="[
                        'Ubiquiti' => 'Ubiquiti',
                        'TP-Link' => 'TP-Link',
                        'MikroTik' => 'MikroTik',
                        'Cambium' => 'Cambium',
                        'Ruckus' => 'Ruckus',
                    ]"
                    :value="old('vendor')"
                    placeholder="Select vendor"
                    :required="true"
                    :error="$errors->first('vendor')"
                />

                <x-ui.input.text
                    label="Model"
                    name="model"
                    placeholder="e.g., LiteBeam 5AC"
                    :value="old('model')"
                    :error="$errors->first('model')"
                />

                <x-ui.input.text
                    label="MAC Address"
                    name="mac_address"
                    placeholder="e.g., AA:BB:CC:DD:EE:FF"
                    :required="true"
                    :value="old('mac_address')"
                    :error="$errors->first('mac_address')"
                />

                <x-ui.input.text
                    label="Serial Number"
                    name="serial_number"
                    placeholder="Device serial number"
                    :value="old('serial_number')"
                    :error="$errors->first('serial_number')"
                />

                <x-ui.input.text
                    label="Firmware Version"
                    name="firmware_version"
                    placeholder="e.g., v8.7.0"
                    :value="old('firmware_version')"
                    :error="$errors->first('firmware_version')"
                />
            </div>
        </div>

        <!-- Section 2: Network Settings -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Network Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    label="Management IP Address"
                    name="ip_address"
                    placeholder="192.168.1.10"
                    :value="old('ip_address')"
                    :error="$errors->first('ip_address')"
                />

                <x-ui.input.text
                    label="SSID"
                    name="ssid"
                    placeholder="WiFi network name"
                    :value="old('ssid')"
                    :error="$errors->first('ssid')"
                />

                <x-ui.input.select
                    label="Frequency Band"
                    name="frequency_band"
                    :options="[
                        '2.4GHz' => '2.4 GHz',
                        '5GHz' => '5 GHz',
                        '6GHz' => '6 GHz',
                        'dual-band' => 'Dual Band',
                    ]"
                    :value="old('frequency_band')"
                    placeholder="Select band"
                    :error="$errors->first('frequency_band')"
                />

                <x-ui.input.text
                    label="Channel"
                    name="channel"
                    placeholder="e.g., 36, Auto"
                    :value="old('channel')"
                    :error="$errors->first('channel')"
                />

                <x-ui.input.text
                    type="number"
                    label="TX Power (dBm)"
                    name="tx_power"
                    placeholder="e.g., 20"
                    :value="old('tx_power')"
                    :error="$errors->first('tx_power')"
                />
            </div>
        </div>

        <!-- Section 3: Physical & Location -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Physical & Location</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.select
                    label="Site"
                    name="site_id"
                    :options="$sites->mapWithKeys(fn ($site) => [$site->id => $site->name . ' (' . $site->code . ')'])->toArray()"
                    :value="old('site_id')"
                    placeholder="Select site"
                    :required="true"
                    :error="$errors->first('site_id')"
                />

                <x-ui.input.select
                    label="Parent Router"
                    name="router_id"
                    :options="$routers->mapWithKeys(fn ($router) => [$router->id => $router->name . ' (' . $router->ip_address . ')'])->toArray()"
                    :value="old('router_id')"
                    placeholder="Select router"
                    :error="$errors->first('router_id')"
                />

                <x-ui.input.select
                    label="Antenna Type"
                    name="antenna_type"
                    :options="[
                        'Omni' => 'Omni',
                        'Directional' => 'Directional',
                        'Sector' => 'Sector',
                    ]"
                    :value="old('antenna_type')"
                    placeholder="Select type"
                    :error="$errors->first('antenna_type')"
                />

                <x-ui.input.text
                    type="number"
                    label="Antenna Gain (dBi)"
                    name="antenna_gain"
                    placeholder="e.g., 14"
                    :value="old('antenna_gain')"
                    :error="$errors->first('antenna_gain')"
                />

                <x-ui.input.text
                    type="number"
                    label="Height (meters)"
                    name="height_meters"
                    placeholder="e.g., 15.5"
                    step="0.01"
                    :value="old('height_meters')"
                    :error="$errors->first('height_meters')"
                />

                <x-ui.input.text
                    type="number"
                    label="Azimuth (degrees)"
                    name="azimuth"
                    placeholder="0-360"
                    min="0"
                    max="360"
                    :value="old('azimuth')"
                    :error="$errors->first('azimuth')"
                />

                <x-ui.input.text
                    type="number"
                    label="Coverage Angle (degrees)"
                    name="coverage_angle"
                    placeholder="e.g., 120"
                    min="0"
                    max="360"
                    :value="old('coverage_angle')"
                    :error="$errors->first('coverage_angle')"
                />
            </div>
        </div>

        <!-- Section 4: Capacity & Status -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Capacity & Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input.text
                    type="number"
                    label="Max Clients"
                    name="max_clients"
                    :value="old('max_clients', 0)"
                    :error="$errors->first('max_clients')"
                />

                <x-ui.input.text
                    type="number"
                    label="Connected Clients"
                    name="connected_clients"
                    :value="old('connected_clients', 0)"
                    :error="$errors->first('connected_clients')"
                />

                <x-ui.input.select
                    label="Status"
                    name="status"
                    :options="[
                        'offline' => 'Offline',
                        'online' => 'Online',
                        'maintenance' => 'Maintenance',
                        'decommissioned' => 'Decommissioned',
                    ]"
                    :value="old('status', 'offline')"
                    :error="$errors->first('status')"
                />

                <div class="md:col-span-2 lg:col-span-3">
                    <x-ui.input.textarea
                        label="Notes"
                        name="notes"
                        placeholder="Additional notes about this access point..."
                        :value="old('notes')"
                        :error="$errors->first('notes')"
                    />
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Action Bar -->
        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('access-points.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Access Point
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
