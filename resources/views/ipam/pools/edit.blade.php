@extends('layouts.admin')

@section('title', 'Edit IP Pool')

@php
// Dummy pool data
$pool = [
    'id' => 1,
    'name' => 'Main Office Network',
    'router' => 'MikroTik RouterBOARD-3011',
    'site' => 'Downtown Office',
    'network_address' => '10.10.0.0',
    'cidr' => 24,
    'gateway' => '10.10.0.1',
    'dns_primary' => '8.8.8.8',
    'dns_secondary' => '8.8.4.4',
    'vlan_id' => 100,
    'type' => 'mixed',
    'status' => 'active',
];

$routers = [
    'MikroTik RouterBOARD-3011',
    'Ubiquiti EdgeRouter 12',
    'Cisco ISR 4431',
    'TP-Link ER707-M2',
    'Juniper MX204',
];

$cidrOptions = [
    8 => '8 (16,777,214 IPs)',
    16 => '16 (65,534 IPs)',
    20 => '20 (4,094 IPs)',
    21 => '21 (2,046 IPs)',
    22 => '22 (1,022 IPs)',
    23 => '23 (510 IPs)',
    24 => '24 (254 IPs)',
    25 => '25 (126 IPs)',
    26 => '26 (62 IPs)',
    27 => '27 (30 IPs)',
    28 => '28 (14 IPs)',
    29 => '29 (6 IPs)',
    30 => '30 (2 IPs)',
];
@endphp

@section('content')
<div class="space-y-6" x-data="editPoolForm()">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('ipam.pools.show', $pool['id']) }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Pool
        </a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit IP Pool</h1>
            <p class="text-sm text-gray-500 mt-1">Update pool configuration and settings</p>
        </div>
    </div>

    <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Section 1: Basic Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Basic Information
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Pool Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pool Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.name" required value="{{ $pool['name'] }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Router -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Router <span class="text-red-500">*</span></label>
                    <select x-model="form.router" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Router</option>
                        @foreach($routers as $router)
                            <option value="{{ $router }}" {{ $pool['router'] === $router ? 'selected' : '' }}>{{ $router }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Site -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Site <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.site" required value="{{ $pool['site'] }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Pool Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pool Type <span class="text-red-500">*</span></label>
                    <select x-model="form.type" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Type</option>
                        <option value="dynamic" {{ $pool['type'] === 'dynamic' ? 'selected' : '' }}>Dynamic (DHCP/PPPoE)</option>
                        <option value="static" {{ $pool['type'] === 'static' ? 'selected' : '' }}>Static (Manual Assignment)</option>
                        <option value="mixed" {{ $pool['type'] === 'mixed' ? 'selected' : '' }}>Mixed (Both)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Dynamic pools are auto-assigned, Static pools require manual assignment</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select x-model="form.status" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" {{ $pool['status'] === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="disabled" {{ $pool['status'] === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Network Configuration -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
                Network Configuration
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Network Address -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Network Address <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.networkAddress" @input="calculatePreview" required value="{{ $pool['network_address'] }}" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                </div>

                <!-- CIDR -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CIDR Prefix <span class="text-red-500">*</span></label>
                    <select x-model="form.cidr" @change="calculatePreview" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select CIDR</option>
                        @foreach($cidrOptions as $cidr => $label)
                            <option value="{{ $cidr }}" {{ $pool['cidr'] == $cidr ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Gateway IP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gateway IP <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.gateway" required value="{{ $pool['gateway'] }}" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                </div>

                <!-- VLAN ID -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">VLAN ID</label>
                    <input type="number" x-model="form.vlanId" min="1" max="4094" value="{{ $pool['vlan_id'] }}" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Primary DNS -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Primary DNS</label>
                    <input type="text" x-model="form.dnsPrimary" value="{{ $pool['dns_primary'] }}" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                </div>

                <!-- Secondary DNS -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Secondary DNS</label>
                    <input type="text" x-model="form.dnsSecondary" value="{{ $pool['dns_secondary'] }}" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                </div>
            </div>

            <!-- Live Preview -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                <h3 class="text-sm font-semibold text-blue-900 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Pool Preview
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700">Network:</span>
                        <span class="font-mono font-semibold text-blue-900" x-text="preview.network"></span>
                    </div>
                    <div>
                        <span class="text-blue-700">Usable IPs:</span>
                        <span class="font-mono font-semibold text-blue-900" x-text="preview.usableIps"></span>
                    </div>
                    <div>
                        <span class="text-blue-700">Range:</span>
                        <span class="font-mono font-semibold text-blue-900" x-text="preview.range"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Advanced Settings -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Advanced Settings
            </h2>

            <div class="space-y-4">
                <!-- Allow Static Assignments -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Allow Static Assignments</h4>
                        <p class="text-xs text-gray-500 mt-1">Enable manual IP assignment from this pool</p>
                    </div>
                    <button type="button" @click="form.allowStatic = !form.allowStatic" :class="form.allowStatic ? 'bg-blue-600' : 'bg-gray-300'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="sr-only">Toggle static assignments</span>
                        <span :class="form.allowStatic ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                <!-- Auto Assign on Provision -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Auto Assign on Provision</h4>
                        <p class="text-xs text-gray-500 mt-1">Automatically assign IPs when creating subscriptions</p>
                    </div>
                    <button type="button" @click="form.autoAssign = !form.autoAssign" :class="form.autoAssign ? 'bg-blue-600' : 'bg-gray-300'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="sr-only">Toggle auto assign</span>
                        <span :class="form.autoAssign ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>

                <!-- Block Reserved Range -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Block Reserved Range</h4>
                        <p class="text-xs text-gray-500 mt-1">Reserve first 10 IPs for infrastructure</p>
                    </div>
                    <button type="button" @click="form.blockReserved = !form.blockReserved" :class="form.blockReserved ? 'bg-blue-600' : 'bg-gray-300'" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <span class="sr-only">Toggle block reserved</span>
                        <span :class="form.blockReserved ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <a href="{{ route('ipam.pools.show', $pool['id']) }}" class="inline-flex items-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function editPoolForm() {
    return {
        form: {
            name: '{{ $pool['name'] }}',
            router: '{{ $pool['router'] }}',
            site: '{{ $pool['site'] }}',
            type: '{{ $pool['type'] }}',
            status: '{{ $pool['status'] }}',
            networkAddress: '{{ $pool['network_address'] }}',
            cidr: {{ $pool['cidr'] }},
            gateway: '{{ $pool['gateway'] }}',
            vlanId: {{ $pool['vlan_id'] }},
            dnsPrimary: '{{ $pool['dns_primary'] }}',
            dnsSecondary: '{{ $pool['dns_secondary'] }}',
            allowStatic: true,
            autoAssign: true,
            blockReserved: true,
        },
        preview: {
            network: '-',
            usableIps: '-',
            range: '-'
        },
        init() {
            this.calculatePreview();
        },
        calculatePreview() {
            if (this.form.networkAddress && this.form.cidr) {
                const cidr = parseInt(this.form.cidr);
                const totalIps = Math.pow(2, 32 - cidr);
                const usableIps = totalIps <= 2 ? totalIps : totalIps - 2;

                this.preview.network = `${this.form.networkAddress}/${this.form.cidr}`;
                this.preview.usableIps = usableIps.toLocaleString();

                const parts = this.form.networkAddress.split('.').map(Number);
                if (usableIps === totalIps) {
                    this.preview.range = `${this.form.networkAddress}`;
                } else {
                    const lastIp = [...parts];
                    lastIp[3] = parts[3] + usableIps + 1;
                    this.preview.range = `${this.form.networkAddress.replace(/\.0$/, '.1')} - ${lastIp.join('.')}`;
                }
            } else {
                this.preview.network = '-';
                this.preview.usableIps = '-';
                this.preview.range = '-';
            }
        },
        submitForm() {
            alert('Pool updated successfully!');
            window.location.href = '{{ route("ipam.pools.show", $pool['id']) }}';
        }
    }
}
</script>
@endpush
@endsection
