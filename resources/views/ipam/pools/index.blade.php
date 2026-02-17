@extends('layouts.admin')

@section('title', 'IP Pools')

@php
// Dummy Data for IP Pools
$ipPools = [
    [
        'id' => 1,
        'name' => 'Main Office Network',
        'router' => 'MikroTik RouterBOARD-3011',
        'site' => 'Downtown Office',
        'network_address' => '10.10.0.0',
        'cidr' => 24,
        'gateway' => '10.10.0.1',
        'vlan_id' => 100,
        'type' => 'mixed',
        'total_ips' => 254,
        'used_ips' => 198,
        'reserved_ips' => 12,
        'available_ips' => 44,
        'status' => 'active',
        'created_at' => '2024-01-15',
    ],
    [
        'id' => 2,
        'name' => 'Guest WiFi Network',
        'router' => 'Ubiquiti EdgeRouter 12',
        'site' => 'Main Street Location',
        'network_address' => '192.168.100.0',
        'cidr' => 24,
        'gateway' => '192.168.100.1',
        'vlan_id' => 200,
        'type' => 'dynamic',
        'total_ips' => 254,
        'used_ips' => 45,
        'reserved_ips' => 5,
        'available_ips' => 204,
        'status' => 'active',
        'created_at' => '2024-02-01',
    ],
    [
        'id' => 3,
        'name' => 'Server Farm Network',
        'router' => 'Cisco ISR 4431',
        'site' => 'Tech Park Building A',
        'network_address' => '172.16.0.0',
        'cidr' => 24,
        'gateway' => '172.16.0.1',
        'vlan_id' => 300,
        'type' => 'static',
        'total_ips' => 254,
        'used_ips' => 238,
        'reserved_ips' => 10,
        'available_ips' => 6,
        'status' => 'exhausted',
        'created_at' => '2024-01-10',
    ],
    [
        'id' => 4,
        'name' => 'IoT Devices Network',
        'router' => 'TP-Link ER707-M2',
        'site' => 'West Street',
        'network_address' => '10.20.0.0',
        'cidr' => 24,
        'gateway' => '10.20.0.1',
        'vlan_id' => 400,
        'type' => 'dynamic',
        'total_ips' => 254,
        'used_ips' => 87,
        'reserved_ips' => 8,
        'available_ips' => 159,
        'status' => 'active',
        'created_at' => '2024-03-01',
    ],
];

$routers = ['MikroTik RouterBOARD-3011', 'Ubiquiti EdgeRouter 12', 'Cisco ISR 4431', 'TP-Link ER707-M2'];
$sites = ['Downtown Office', 'Main Street Location', 'Tech Park Building A', 'West Street'];

function getPoolStatusBadge($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'exhausted' => 'bg-red-100 text-red-800 border-red-200',
        'disabled' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    return $classes[$status] ?? $classes['active'];
}

function getPoolTypeBadge($type)
{
    $classes = [
        'dynamic' => 'bg-blue-100 text-blue-800 border-blue-200',
        'static' => 'bg-purple-100 text-purple-800 border-purple-200',
        'mixed' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    ];

    return $classes[$type] ?? $classes['dynamic'];
}

function getUsageColor($percentage)
{
    if ($percentage >= 90) return 'red';
    if ($percentage >= 80) return 'yellow';
    return 'green';
}
@endphp

@section('content')
<div class="space-y-6" x-data="ipamPools()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">IP Pools</h1>
            <p class="text-sm text-gray-500 mt-1">Manage network address pools and allocations</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('ipam.pools.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Pool
            </a>

            <button class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Import Pool
            </button>

            <button @click="window.location.reload()" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <div x-data="{ showFilters: true }" class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Filters</h3>
                <button @click="showFilters = !showFilters" class="text-sm text-gray-500 hover:text-gray-700">
                    <span x-text="showFilters ? 'Hide' : 'Show'"></span>
                </button>
            </div>

            <div x-show="showFilters" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Router -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Router</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Routers</option>
                        @foreach($routers as $router)
                            <option value="{{ $router }}">{{ $router }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Site -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Site</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Sites</option>
                        @foreach($sites as $site)
                            <option value="{{ $site }}">{{ $site }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="exhausted">Exhausted</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>

                <!-- Pool Type -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pool Type</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="dynamic">Dynamic</option>
                        <option value="static">Static</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search Pool</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" placeholder="Pool name..." class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Clear Filters Button -->
                <div class="lg:col-span-5 flex items-center justify-end">
                    <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pools Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pool Name</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Site</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Network / CIDR</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Gateway</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">VLAN</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total IPs</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Used</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Available</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ipPools as $pool)
                        @php
                            $usagePercent = round(($pool['used_ips'] / $pool['total_ips']) * 100);
                            $usageColor = getUsageColor($usagePercent);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('ipam.pools.show', $pool['id']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                        {{ $pool['name'] }}
                                    </a>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border {{ getPoolTypeBadge($pool['type']) }}">
                                        {{ ucfirst($pool['type']) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $pool['router'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $pool['site'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                    {{ $pool['network_address'] }}/{{ $pool['cidr'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-mono">{{ $pool['gateway'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">VLAN {{ $pool['vlan_id'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($pool['total_ips']) }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($pool['used_ips']) }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($pool['available_ips']) }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="w-full max-w-[120px]">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-600">{{ $usagePercent }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-{{ $usageColor }}-500 h-2 rounded-full transition-all duration-500" style="width: {{ $usagePercent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getPoolStatusBadge($pool['status']) }}">
                                    {{ ucfirst($pool['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button @click="open = !open" @click.outside="open = false" class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                        </svg>
                                    </button>

                                    <div x-show="open"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 mt-2 w-48 rounded-xl shadow-lg bg-white border border-gray-200 py-1 z-50"
                                         style="display: none;">
                                        <a href="{{ route('ipam.pools.show', $pool['id']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View Pool</a>
                                        <a href="{{ route('ipam.pools.edit', $pool['id']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit Pool</a>
                                        @if($pool['status'] === 'active')
                                            <a href="#" class="block px-4 py-2 text-sm text-yellow-600 hover:bg-gray-50">Disable</a>
                                        @else
                                            <a href="#" class="block px-4 py-2 text-sm text-green-600 hover:bg-gray-50">Enable</a>
                                        @endif
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Delete Pool</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ipamPools() {
    return {
        init() {
            console.log('IPAM Pools initialized');
        }
    }
}
</script>
@endpush
@endsection
