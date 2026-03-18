@extends('layouts.admin')

@section('title', 'Edit IP Pool')

@section('content')
<div class="space-y-6" x-data="editPoolForm()">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('ipam.pools.show', $pool->id) }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
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

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex">
            <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('ipam.pools.update', $pool->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

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
                <x-input.text
                    id="name"
                    name="name"
                    label="Pool Name"
                    :value="$pool->name"
                    required
                    xModel="form.name"
                />

                <!-- Router -->
                <div>
                    <label for="router_id" class="block text-sm font-medium text-gray-700">
                        Router <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="router_id"
                        name="router_id"
                        x-model="form.routerId"
                        required
                        class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="">Select Router</option>
                        @foreach($routers as $id => $name)
                            <option value="{{ $id }}" {{ $pool->router_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('router_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Site -->
                <x-input.text
                    id="site"
                    name="site"
                    label="Site"
                    :value="$pool->site"
                    :options="collect($sites)->map(fn($s) => ['value' => $s, 'label' => $s])->toArray()"
                    list="sites"
                    xModel="form.site"
                />

                <!-- Pool Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">
                        Pool Type <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="type"
                        name="type"
                        x-model="form.type"
                        required
                        class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="">Select Type</option>
                        <option value="dynamic" {{ $pool->type === 'dynamic' ? 'selected' : '' }}>Dynamic (DHCP/PPPoE)</option>
                        <option value="static" {{ $pool->type === 'static' ? 'selected' : '' }}>Static (Manual Assignment)</option>
                        <option value="mixed" {{ $pool->type === 'mixed' ? 'selected' : '' }}>Mixed (Both)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Dynamic pools are auto-assigned, Static pools require manual assignment</p>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">
                        Status
                    </label>
                    <select
                        id="status"
                        name="status"
                        x-model="form.status"
                        class="mt-1 block w-full rounded-lg border border-gray-300 shadow-sm px-3 py-2 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                        <option value="active" {{ $pool->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="disabled" {{ $pool->status === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
                <x-input.ip-address
                    id="network_address"
                    name="network_address"
                    label="Network Address"
                    :value="$pool->network_address"
                    required
                    xModel="form.networkAddress"
                    xModelDebounce="300"
                />

                <!-- CIDR -->
                <x-input.cidr
                    id="cidr"
                    name="cidr"
                    label="CIDR Prefix"
                    :value="$pool->cidr"
                    required
                    xModel="form.cidr"
                    xChange="calculatePreview"
                />

                <!-- Gateway IP -->
                <x-input.ip-address
                    id="gateway"
                    name="gateway"
                    label="Gateway IP"
                    :value="$pool->gateway"
                    required
                    xModel="form.gateway"
                />

                <!-- VLAN ID -->
                <x-input.number
                    id="vlan_id"
                    name="vlan_id"
                    label="VLAN ID"
                    :value="$pool->vlan_id"
                    min="1"
                    max="4094"
                    xModel="form.vlanId"
                />

                <!-- Primary DNS -->
                <x-input.ip-address
                    id="dns_primary"
                    name="dns_primary"
                    label="Primary DNS"
                    :value="$pool->dns_primary"
                    xModel="form.dnsPrimary"
                />

                <!-- Secondary DNS -->
                <x-input.ip-address
                    id="dns_secondary"
                    name="dns_secondary"
                    label="Secondary DNS"
                    :value="$pool->dns_secondary"
                    xModel="form.dnsSecondary"
                />
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
                <x-input.switch
                    id="allow_static"
                    name="allow_static"
                    label="Allow Static Assignments"
                    help="Enable manual IP assignment from this pool"
                    :checked="$pool->allow_static"
                    xModel="form.allowStatic"
                />

                <!-- Auto Assign on Provision -->
                <x-input.switch
                    id="auto_assign"
                    name="auto_assign"
                    label="Auto Assign on Provision"
                    help="Automatically assign IPs when creating subscriptions"
                    :checked="$pool->auto_assign"
                    xModel="form.autoAssign"
                />

                <!-- Block Reserved Range -->
                <x-input.switch
                    id="block_reserved"
                    name="block_reserved"
                    label="Block Reserved Range"
                    help="Reserve first 10 IPs for infrastructure"
                    :checked="$pool->block_reserved"
                    xModel="form.blockReserved"
                />
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <a href="{{ route('ipam.pools.show', $pool->id) }}" class="inline-flex items-center gap-2 px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
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
            name: '{{ $pool->name }}',
            routerId: '{{ $pool->router_id }}',
            site: '{{ $pool->site }}',
            type: '{{ $pool->type }}',
            status: '{{ $pool->status }}',
            networkAddress: '{{ $pool->network_address }}',
            cidr: {{ $pool->cidr }},
            gateway: '{{ $pool->gateway }}',
            vlanId: '{{ $pool->vlan_id }}',
            dnsPrimary: '{{ $pool->dns_primary }}',
            dnsSecondary: '{{ $pool->dns_secondary }}',
            allowStatic: {{ $pool->allow_static ? 'true' : 'false' }},
            autoAssign: {{ $pool->auto_assign ? 'true' : 'false' }},
            blockReserved: {{ $pool->block_reserved ? 'true' : 'false' }},
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
                this.preview.usableIps = Math.min(usableIps, 254).toLocaleString();

                const parts = this.form.networkAddress.split('.').map(Number);
                if (usableIps === totalIps) {
                    this.preview.range = `${this.form.networkAddress}`;
                } else {
                    const lastIp = [...parts];
                    const displayUsableIps = Math.min(usableIps, 254);
                    lastIp[3] = parts[3] + displayUsableIps;
                    this.preview.range = `${this.form.networkAddress.replace(/\.0$/, '.1')} - ${lastIp.join('.')}`;
                }
            } else {
                this.preview.network = '-';
                this.preview.usableIps = '-';
                this.preview.range = '-';
            }
        }
    }
}
</script>
@endpush
@endsection
