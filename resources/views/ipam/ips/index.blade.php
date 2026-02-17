@extends('layouts.admin')

@section('title', 'All IP Addresses')

@php
// Generate comprehensive dummy IP data
$allIPs = [];
$pools = [
    ['id' => 1, 'name' => 'Main Office Network', 'router' => 'MikroTik RouterBOARD-3011', 'network' => '10.10.0.0/24'],
    ['id' => 2, 'name' => 'Guest WiFi Network', 'router' => 'Ubiquiti EdgeRouter 12', 'network' => '192.168.100.0/24'],
    ['id' => 3, 'name' => 'Server Farm Network', 'router' => 'Cisco ISR 4431', 'network' => '172.16.0.0/24'],
    ['id' => 4, 'name' => 'IoT Devices Network', 'router' => 'TP-Link ER707-M2', 'network' => '10.20.0.0/24'],
];

$customers = ['Acme Corporation', 'Smith Residence', 'Tech Startup Inc', 'Downtown Cafe', 'Johnson Family', 'Global Logistics Ltd'];

// Generate IPs for each pool
foreach ($pools as $pool) {
    $baseIp = explode('.', $pool['network'])[0] . '.' . explode('.', $pool['network'])[1] . '.0';

    for ($i = 1; $i <= 50; $i++) {
        $ip = str_replace('.0', ".{$i}", $baseIp);

        // Determine status based on IP number
        if ($i === 1) {
            $status = 'reserved';
            $customer = null;
            $subscription = null;
            $assignedAt = null;
        } elseif ($i <= 10) {
            $status = 'reserved';
            $customer = null;
            $subscription = null;
            $assignedAt = null;
        } elseif (in_array($i, [11, 12, 13])) {
            $status = 'blocked';
            $customer = null;
            $subscription = null;
            $assignedAt = null;
        } elseif ($i <= 35) {
            $status = 'assigned';
            $customer = $customers[array_rand($customers)];
            $subscription = 'SUB-2024-' . str_pad($i + 100, 3, '0', STR_PAD_LEFT);
            $assignedAt = '2024-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        } else {
            $status = 'available';
            $customer = null;
            $subscription = null;
            $assignedAt = null;
        }

        $allIPs[] = [
            'id' => count($allIPs) + 1,
            'ip_address' => $ip,
            'pool_name' => $pool['name'],
            'pool_id' => $pool['id'],
            'router' => $pool['router'],
            'status' => $status,
            'customer_name' => $customer,
            'subscription_code' => $subscription,
            'assigned_at' => $assignedAt,
        ];
    }
}

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
@endphp

@section('content')
<div class="space-y-6" x-data="ipList()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All IP Addresses</h1>
            <p class="text-sm text-gray-500 mt-1">View and manage all IP addresses across all pools</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('ipam.pools.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Manage Pools
            </a>
            <a href="{{ route('ipam.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>
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

            <div x-show="showFilters" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Pool -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pool</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Pools</option>
                        @foreach($pools as $pool)
                            <option value="{{ $pool['id'] }}">{{ $pool['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Router -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Router</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Routers</option>
                        @foreach($pools as $pool)
                            <option value="{{ $pool['router'] }}">{{ $pool['router'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="available">Available</option>
                        <option value="assigned">Assigned</option>
                        <option value="reserved">Reserved</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <!-- Customer -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Customer</label>
                    <input type="text" placeholder="Customer name..." class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- IP Search -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search IP</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" placeholder="e.g., 10.10.0.1" class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                    </div>
                </div>

                <!-- Clear Filters -->
                <div class="flex items-end">
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $totalCount = count($allIPs);
            $assignedCount = count(array_filter($allIPs, fn($ip) => $ip['status'] === 'assigned'));
            $availableCount = count(array_filter($allIPs, fn($ip) => $ip['status'] === 'available'));
            $reservedCount = count(array_filter($allIPs, fn($ip) => $ip['status'] === 'reserved'));
        @endphp

        <div class="bg-white rounded-xl p-4 border border-gray-200">
            <div class="text-xs font-medium text-gray-500">Total IPs</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalCount) }}</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-200">
            <div class="text-xs font-medium text-gray-500">Assigned</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($assignedCount) }}</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-200">
            <div class="text-xs font-medium text-gray-500">Available</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ number_format($availableCount) }}</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-gray-200">
            <div class="text-xs font-medium text-gray-500">Reserved</div>
            <div class="text-2xl font-bold text-yellow-600 mt-1">{{ number_format($reservedCount) }}</div>
        </div>
    </div>

    <!-- IP Addresses Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pool</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Assigned At</th>
                        <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($allIPs as $ip)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <a href="{{ route('ipam.ips.show', $ip['ip_address']) }}" class="text-sm font-mono font-medium text-blue-600 hover:text-blue-800">
                                    {{ $ip['ip_address'] }}
                                </a>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <a href="{{ route('ipam.pools.show', $ip['pool_id']) }}" class="text-sm text-gray-900 hover:text-blue-600">
                                    {{ $ip['pool_name'] }}
                                </a>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $ip['router'] }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getIpStatusBadge($ip['status']) }}">
                                    {{ ucfirst($ip['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $ip['customer_name'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-mono">{{ $ip['subscription_code'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $ip['assigned_at'] ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('ipam.ips.show', $ip['ip_address']) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span class="font-medium">1</span> to <span class="font-medium">{{ min(50, count($allIPs)) }}</span> of <span class="font-medium">{{ count($allIPs) }}</span> results
            </div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Next</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ipList() {
    return {
        init() {
            console.log('IP List initialized');
        }
    }
}
</script>
@endpush
@endsection
