@extends('layouts.admin')

@section('title', 'IP Pool Details')

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
    'total_ips' => 254,
    'used_ips' => 198,
    'reserved_ips' => 12,
    'available_ips' => 44,
    'status' => 'active',
    'created_at' => '2024-01-15',
];

// Generate dummy IP addresses
$ipAddresses = [];
for ($i = 1; $i <= 254; $i++) {
    $ip = "10.10.0.{$i}";

    // Skip gateway
    if ($i == 1) {
        $status = 'reserved';
        $customer = null;
        $subscription = null;
        $mac = null;
        $assignedAt = null;
        $notes = 'Gateway';
    } elseif ($i <= 10) {
        $status = 'reserved';
        $customer = null;
        $subscription = null;
        $mac = null;
        $assignedAt = null;
        $notes = 'Infrastructure';
    } elseif ($i == 254) {
        $status = 'reserved';
        $customer = null;
        $subscription = null;
        $mac = null;
        $assignedAt = null;
        $notes = 'Broadcast';
    } elseif (in_array($i, [11, 12, 13])) {
        $status = 'blocked';
        $customer = null;
        $subscription = null;
        $mac = null;
        $assignedAt = null;
        $notes = 'Blocked - Abuse';
    } elseif (in_array($i, [14, 15, 16, 17, 18, 19, 20])) {
        $status = 'reserved';
        $customer = 'Reserved for VIP';
        $subscription = null;
        $mac = null;
        $assignedAt = null;
        $notes = 'Reserved';
    } elseif ($i <= 100) {
        $status = 'assigned';
        $customer = 'Customer ' . chr(65 + ($i % 26));
        $subscription = 'SUB-2024-' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $mac = 'AA:BB:CC:' . str_pad(dechex($i), 2, '0', STR_PAD_LEFT) . ':DD:EE';
        $assignedAt = '2024-01-' . str_pad(($i % 28) + 1, 2, '0', STR_PAD_LEFT);
        $notes = '';
    } else {
        $status = 'available';
        $customer = null;
        $subscription = null;
        $mac = null;
        $assignedAt = null;
        $notes = '';
    }

    $ipAddresses[] = [
        'id' => $i,
        'ip_address' => $ip,
        'pool_name' => $pool['name'],
        'router' => $pool['router'],
        'status' => $status,
        'customer_name' => $customer,
        'subscription_code' => $subscription,
        'mac_address' => $mac,
        'assigned_at' => $assignedAt,
        'notes' => $notes,
    ];
}

$usagePercent = round(($pool['used_ips'] / $pool['total_ips']) * 100);
$usageColor = $usagePercent >= 90 ? 'red' : ($usagePercent >= 80 ? 'yellow' : 'green');

function getIpStatusBadge($status)
{
    $classes = [
        'available' => 'bg-green-100 text-green-800 border-green-200',
        'assigned' => 'bg-blue-100 text-blue-800 border-blue-200',
        'reserved' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'blocked' => 'bg-red-100 text-red-800 border-red-200',
    ];

    return $classes[$status] ?? $classes['available'];
}

function getIpRowBg($status)
{
    $classes = [
        'available' => 'bg-green-50/30',
        'assigned' => 'bg-blue-50/30',
        'reserved' => 'bg-yellow-50/30',
        'blocked' => 'bg-red-50/30',
    ];

    return $classes[$status] ?? '';
}
@endphp

@section('content')
<div class="space-y-6" x-data="poolShow()">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('ipam.pools.index') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Pools
        </a>
    </div>

    <!-- Top Summary -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold">{{ $pool['name'] }}</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 border border-white/30">
                        {{ ucfirst($pool['type']) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-blue-100">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                        {{ $pool['router'] }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ $pool['site'] }}
                    </span>
                    <span class="flex items-center gap-1 font-mono">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        {{ $pool['network_address'] }}/{{ $pool['cidr'] }}
                    </span>
                    <span class="flex items-center gap-1 font-mono">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{ $pool['gateway'] }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('ipam.pools.edit', $pool['id']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Pool
                </a>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Export IPs
                </button>
            </div>
        </div>

        <!-- Usage Bar -->
        <div class="mt-6">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-blue-100">Pool Usage</span>
                <span class="font-semibold">{{ $usagePercent }}%</span>
            </div>
            <div class="w-full bg-white/20 rounded-full h-3">
                <div class="bg-{{ $usageColor }}-500 h-3 rounded-full transition-all duration-500" style="width: {{ $usagePercent }}%"></div>
            </div>
        </div>
    </div>

    <!-- Usage Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total IPs -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Total IPs</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($pool['total_ips']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Used -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Used</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($pool['used_ips']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Reserved -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Reserved</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($pool['reserved_ips']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-yellow-50 text-yellow-600 border border-yellow-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Available -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500">Available</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($pool['available_ips']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 border border-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- IP Address Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">IP Addresses</h2>
            <div class="flex items-center gap-3">
                <input type="text" placeholder="Search IP..." class="block px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-48">
                <select class="block px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="assigned">Assigned</option>
                    <option value="reserved">Reserved</option>
                    <option value="blocked">Blocked</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">MAC Address</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ipAddresses as $ip)
                        <tr class="hover:bg-gray-50 transition-colors duration-150 {{ getIpRowBg($ip['status']) }}">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('ipam.ips.show', $ip['ip_address']) }}" class="text-sm font-mono font-medium text-blue-600 hover:text-blue-800">
                                    {{ $ip['ip_address'] }}
                                </a>
                                @if($ip['notes'])
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $ip['notes'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getIpStatusBadge($ip['status']) }}">
                                    {{ ucfirst($ip['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $ip['customer_name'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-mono">{{ $ip['subscription_code'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-mono">{{ $ip['mac_address'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $ip['assigned_at'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                @if($ip['status'] === 'available')
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="assignIp('{{ $ip['ip_address'] }}')" class="text-green-600 hover:text-green-800 text-xs font-medium">Assign</button>
                                        <button @click="reserveIp('{{ $ip['ip_address'] }}')" class="text-yellow-600 hover:text-yellow-800 text-xs font-medium">Reserve</button>
                                        <button @click="blockIp('{{ $ip['ip_address'] }}')" class="text-red-600 hover:text-red-800 text-xs font-medium">Block</button>
                                    </div>
                                @elseif($ip['status'] === 'assigned')
                                    <div class="flex items-center justify-end gap-1">
                                        <button class="text-blue-600 hover:text-blue-800 text-xs font-medium">View Customer</button>
                                        <button @click="releaseIp('{{ $ip['ip_address'] }}')" class="text-gray-600 hover:text-gray-800 text-xs font-medium">Release</button>
                                    </div>
                                @elseif($ip['status'] === 'reserved')
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="assignIp('{{ $ip['ip_address'] }}')" class="text-green-600 hover:text-green-800 text-xs font-medium">Assign</button>
                                        <button @click="releaseIp('{{ $ip['ip_address'] }}')" class="text-gray-600 hover:text-gray-800 text-xs font-medium">Release</button>
                                    </div>
                                @elseif($ip['status'] === 'blocked')
                                    <button @click="unblockIp('{{ $ip['ip_address'] }}')" class="text-blue-600 hover:text-blue-800 text-xs font-medium">Unblock</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Assign Modal -->
<div x-data="{ show: false, ip: '' }" x-show="show" class="relative z-50" style="display: none;">
    <div x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
         @click="show = false"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Assign IP Address</h3>
                    <p class="text-sm text-gray-500 mt-1">Assign IP: <span class="font-mono font-semibold" x-text="ip"></span></p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Customer</option>
                                <option value="1">Acme Corporation</option>
                                <option value="2">Smith Residence</option>
                                <option value="3">Tech Startup Inc</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subscription</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Subscription</option>
                                <option value="1">SUB-2024-001</option>
                                <option value="2">SUB-2024-002</option>
                                <option value="3">SUB-2024-003</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">MAC Address</label>
                            <input type="text" placeholder="AA:BB:CC:DD:EE:FF" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" @click="show = false" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Assign IP</button>
                    <button type="button" @click="show = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function poolShow() {
    return {
        init() {
            console.log('Pool Show initialized');
        },
        assignIp(ip) {
            this.$dispatch('open-assign-modal', { ip: ip });
        },
        reserveIp(ip) {
            if (confirm(`Reserve IP ${ip}?`)) {
                alert(`IP ${ip} has been reserved`);
            }
        },
        blockIp(ip) {
            if (confirm(`Block IP ${ip}?`)) {
                alert(`IP ${ip} has been blocked`);
            }
        },
        releaseIp(ip) {
            if (confirm(`Release IP ${ip}?`)) {
                alert(`IP ${ip} has been released`);
            }
        },
        unblockIp(ip) {
            if (confirm(`Unblock IP ${ip}?`)) {
                alert(`IP ${ip} has been unblocked`);
            }
        }
    }
}

// Listen for modal open event
document.addEventListener('open-assign-modal', (e) => {
    const modal = document.querySelector('[x-data*="show: false"]');
    if (modal && modal._x_dataStack) {
        modal._x_dataStack[0].ip = e.detail.ip;
        modal._x_dataStack[0].show = true;
    }
});
</script>
@endpush
@endsection
