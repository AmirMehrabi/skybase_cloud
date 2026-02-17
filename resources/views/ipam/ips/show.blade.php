@extends('layouts.admin')

@section('title', 'IP Address Details')

@php
// Dummy IP data
$ipAddress = [
    'id' => 1,
    'ip_address' => '10.10.0.100',
    'pool_name' => 'Main Office Network',
    'pool_id' => 1,
    'router' => 'MikroTik RouterBOARD-3011',
    'network_address' => '10.10.0.0',
    'cidr' => 24,
    'gateway' => '10.10.0.1',
    'status' => 'assigned',
    'customer_name' => 'Acme Corporation',
    'customer_id' => 'CUST-001',
    'subscription_code' => 'SUB-2024-001',
    'subscription_id' => 1,
    'mac_address' => 'AA:BB:CC:DD:EE:01',
    'assigned_at' => '2024-01-15',
    'assigned_by' => 'admin',
    'notes' => 'Static assignment for CEO office',
];

$customers = [
    ['id' => 1, 'name' => 'Acme Corporation'],
    ['id' => 2, 'name' => 'Smith Residence'],
    ['id' => 3, 'name' => 'Tech Startup Inc'],
    ['id' => 4, 'name' => 'Downtown Cafe'],
    ['id' => 5, 'name' => 'Johnson Family'],
];

$subscriptions = [
    ['id' => 1, 'code' => 'SUB-2024-001'],
    ['id' => 2, 'code' => 'SUB-2024-002'],
    ['id' => 3, 'code' => 'SUB-2024-003'],
];

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
<div class="space-y-6" x-data="ipDetail()">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ request()->header('referer') ?? route('ipam.ips.index') }}" class="inline-flex items-center text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to IPs
        </a>
    </div>

    <!-- Top Summary Card -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold font-mono">{{ $ipAddress['ip_address'] }}</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 border border-white/30">
                        {{ ucfirst($ipAddress['status']) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-blue-100">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        {{ $ipAddress['pool_name'] }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                        {{ $ipAddress['router'] }}
                    </span>
                    <span class="flex items-center gap-1 font-mono">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        {{ $ipAddress['network_address'] }}/{{ $ipAddress['cidr'] }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('ipam.pools.show', $ipAddress['pool_id']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    View Pool
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Assignment Information -->
            @if($ipAddress['status'] === 'assigned')
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Assignment Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Customer</label>
                        <div class="text-sm font-semibold text-gray-900">{{ $ipAddress['customer_name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $ipAddress['customer_id'] }}</div>
                    </div>

                    <!-- Subscription -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Subscription</label>
                        <a href="#" class="text-sm font-semibold text-blue-600 hover:text-blue-800 font-mono">{{ $ipAddress['subscription_code'] }}</a>
                    </div>

                    <!-- MAC Address -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">MAC Address</label>
                        <div class="text-sm font-mono text-gray-900">{{ $ipAddress['mac_address'] }}</div>
                    </div>

                    <!-- Assigned Date -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Assigned Date</label>
                        <div class="text-sm text-gray-900">{{ $ipAddress['assigned_at'] }}</div>
                    </div>

                    <!-- Notes -->
                    @if($ipAddress['notes'])
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <div class="text-sm text-gray-900">{{ $ipAddress['notes'] }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Network Information -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    Network Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Pool</label>
                        <a href="{{ route('ipam.pools.show', $ipAddress['pool_id']) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">{{ $ipAddress['pool_name'] }}</a>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Router</label>
                        <div class="text-sm text-gray-900">{{ $ipAddress['router'] }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Network</label>
                        <div class="text-sm font-mono text-gray-900">{{ $ipAddress['network_address'] }}/{{ $ipAddress['cidr'] }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Gateway</label>
                        <div class="text-sm font-mono text-gray-900">{{ $ipAddress['gateway'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Actions -->
        <div class="space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Current Status</h3>
                <div class="flex items-center justify-center py-4">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border {{ getIpStatusBadge($ipAddress['status']) }}">
                        {{ ucfirst($ipAddress['status']) }}
                    </span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Quick Actions</h3>

                <div class="space-y-3">
                    @if($ipAddress['status'] === 'available')
                        <button @click="assignIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Assign to Customer
                        </button>

                        <button @click="reserveIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Reserve IP
                        </button>

                        <button @click="blockIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                            Block IP
                        </button>

                    @elseif($ipAddress['status'] === 'assigned')
                        <a href="#" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            View Customer
                        </a>

                        <button @click="reassignIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Reassign
                        </button>

                        <button @click="releaseIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Release IP
                        </button>

                    @elseif($ipAddress['status'] === 'reserved')
                        <button @click="assignIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Assign to Customer
                        </button>

                        <button @click="releaseIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Release IP
                        </button>

                    @elseif($ipAddress['status'] === 'blocked')
                        <button @click="unblockIp()" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            Unblock IP
                        </button>
                    @endif
                </div>
            </div>

            <!-- Activity Log -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Activity</h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <div class="text-sm text-gray-900">IP assigned to Acme Corporation</div>
                            <div class="text-xs text-gray-500">Jan 15, 2024 by admin</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <div class="text-sm text-gray-900">IP marked as available</div>
                            <div class="text-xs text-gray-500">Jan 10, 2024 by system</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign IP Modal -->
<div x-data="{ show: false }" x-show="show" class="relative z-50" style="display: none;">
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
                    <p class="text-sm text-gray-500 mt-1">Assign IP: <span class="font-mono font-semibold">{{ $ipAddress['ip_address'] }}</span></p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer['id'] }}" {{ $ipAddress['customer_name'] === $customer['name'] ? 'selected' : '' }}>{{ $customer['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subscription</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Subscription</option>
                                @foreach($subscriptions as $sub)
                                    <option value="{{ $sub['id'] }}" {{ $ipAddress['subscription_code'] === $sub['code'] ? 'selected' : '' }}>{{ $sub['code'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">MAC Address</label>
                            <input type="text" value="{{ $ipAddress['mac_address'] ?? '' }}" placeholder="AA:BB:CC:DD:EE:FF" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea rows="2" placeholder="Add notes..." class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">{{ $ipAddress['notes'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" @click="show = false; confirmAssign()" class="inline-flex w-full justify-center rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto">Assign IP</button>
                    <button type="button" @click="show = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ipDetail() {
    return {
        init() {
            console.log('IP Detail initialized');
        },
        assignIp() {
            this.$dispatch('open-assign-modal');
        },
        reserveIp() {
            if (confirm('Reserve this IP address?')) {
                alert('IP has been reserved');
            }
        },
        blockIp() {
            if (confirm('Block this IP address?')) {
                alert('IP has been blocked');
            }
        },
        reassignIp() {
            this.$dispatch('open-assign-modal');
        },
        releaseIp() {
            if (confirm('Release this IP address?')) {
                alert('IP has been released');
                window.location.reload();
            }
        },
        unblockIp() {
            if (confirm('Unblock this IP address?')) {
                alert('IP has been unblocked');
            }
        },
        confirmAssign() {
            alert('IP has been assigned');
            window.location.reload();
        }
    }
}

// Listen for modal open event
document.addEventListener('open-assign-modal', () => {
    const modals = document.querySelectorAll('[x-data*="show: false"]');
    modals.forEach(modal => {
        if (modal._x_dataStack && modal._x_dataStack[0].show !== undefined) {
            modal._x_dataStack[0].show = true;
        }
    });
});
</script>
@endpush
@endsection
