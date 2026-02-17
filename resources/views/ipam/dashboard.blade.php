@extends('layouts.admin')

@section('title', 'IPAM Dashboard')

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
        'dns_primary' => '8.8.8.8',
        'dns_secondary' => '8.8.4.4',
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
        'dns_primary' => '1.1.1.1',
        'dns_secondary' => '1.0.0.1',
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
        'dns_primary' => '8.8.8.8',
        'dns_secondary' => '8.8.4.4',
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
        'dns_primary' => '8.8.8.8',
        'dns_secondary' => '8.8.4.4',
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

// Calculate totals
$totalPools = count($ipPools);
$totalIPs = array_sum(array_column($ipPools, 'total_ips'));
$usedIPs = array_sum(array_column($ipPools, 'used_ips'));
$availableIPs = array_sum(array_column($ipPools, 'available_ips'));
$reservedIPs = array_sum(array_column($ipPools, 'reserved_ips'));
$exhaustedPools = count(array_filter($ipPools, fn($p) => $p['status'] === 'exhausted'));

function getPoolStatusBadge($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'exhausted' => 'bg-red-100 text-red-800 border-red-200',
        'disabled' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    return $classes[$status] ?? $classes['active'];
}

function getUsageColor($percentage)
{
    if ($percentage >= 90) return 'red';
    if ($percentage >= 80) return 'yellow';
    return 'green';
}
@endphp

@section('content')
<div class="space-y-6" x-data="ipamDashboard()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">IP Address Management</h1>
            <p class="text-sm text-gray-500 mt-1">Monitor and manage network address allocation</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('ipam.pools.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Manage Pools
            </a>
            <a href="{{ route('ipam.ips.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
                All IPs
            </a>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- Total Pools -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Total Pools</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalPools }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total IPs -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Total IPs</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalIPs) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Used IPs -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Used IPs</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($usedIPs) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Available IPs -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Available IPs</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($availableIPs) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 border border-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Reserved IPs -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Reserved IPs</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($reservedIPs) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-yellow-50 text-yellow-600 border border-yellow-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Exhausted Pools -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Exhausted Pools</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $exhaustedPools }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Section -->
    @php
        $warningPools = array_filter($ipPools, fn($p) => ($p['used_ips'] / $p['total_ips']) * 100 > 80);
    @endphp
    @if(count($warningPools) > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-yellow-800">Pool Nearing Exhaustion</h3>
                <p class="text-sm text-yellow-700 mt-1">The following pools have less than 20% available IPs:</p>
                <ul class="mt-2 space-y-1">
                    @foreach($warningPools as $pool)
                        @php
                            $usagePercent = round(($pool['used_ips'] / $pool['total_ips']) * 100);
                        @endphp
                        <li class="text-sm text-yellow-700 flex items-center gap-2">
                            <span class="font-medium">{{ $pool['name'] }}</span>
                            <span class="text-yellow-600">({{ $usagePercent }}% used)</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Pool Usage Overview Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Pool Usage Overview</h2>
            <a href="{{ route('ipam.pools.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All Pools →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pool Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">CIDR</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Used / Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
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
                                <a href="{{ route('ipam.pools.show', $pool['id']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    {{ $pool['name'] }}
                                </a>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $pool['router'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                    {{ $pool['network_address'] }}/{{ $pool['cidr'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="w-full max-w-[140px]">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-600">{{ $usagePercent }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-{{ $usageColor }}-500 h-2 rounded-full transition-all duration-500" style="width: {{ $usagePercent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ number_format($pool['used_ips']) }} / {{ number_format($pool['total_ips']) }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getPoolStatusBadge($pool['status']) }}">
                                    {{ ucfirst($pool['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('ipam.pools.show', $pool['id']) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View</a>
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
function ipamDashboard() {
    return {
        init() {
            console.log('IPAM Dashboard initialized');
        }
    }
}
</script>
@endpush
@endsection
