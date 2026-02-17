@section('title', 'Customers')

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and monitor your ISP subscribers</p>
        </div>
        <div class="flex items-center gap-3">
            <x-ui.dropdown align="right">
                <x-slot:trigger>
                    <x-ui.button variant="secondary" size="md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </x-ui.button>
                </x-slot:trigger>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as CSV</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as PDF</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as Excel</a>
            </x-ui.dropdown>
            <a href="{{ route('customers.create') }}">
                <x-ui.button variant="primary" size="md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Customer
                </x-ui.button>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <x-ui.stat-card
            title="Total Customers"
            :value="$stats['total']"
            :color="'blue'"
        >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </x-ui.stat-card>

        <x-ui.stat-card
            title="Active Customers"
            :value="$stats['active']"
            :color="'green'"
        >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </x-ui.stat-card>

        <x-ui.stat-card
            title="Suspended"
            :value="$stats['suspended']"
            :color="'yellow'"
        >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </x-ui.stat-card>

        <x-ui.stat-card
            title="Overdue"
            :value="$stats['overdue']"
            :color="'red'"
        >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </x-ui.stat-card>
    </div>

    <!-- Filters Card -->
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Filters</h3>
            @if($search || $status || $plan || $site || $router)
                <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Clear All
                </button>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="xl:col-span-2">
                <x-ui.input.text
                    name="search"
                    placeholder="Search by name, email, phone..."
                    wire:model.live="search"
                />
            </div>

            <!-- Status Filter -->
            <x-ui.input.select
                name="status"
                placeholder="All Statuses"
                :options="['' => 'All Statuses', 'active' => 'Active', 'suspended' => 'Suspended', 'terminated' => 'Terminated', 'pending' => 'Pending']"
                wire:model.live="status"
            />

            <!-- Plan Filter -->
            <x-ui.input.select
                name="plan"
                placeholder="All Plans"
                :options="['' => 'All Plans', 'Fiber 50 Mbps' => 'Fiber 50 Mbps', 'Fiber 100 Mbps' => 'Fiber 100 Mbps', 'Fiber 200 Mbps' => 'Fiber 200 Mbps', 'Fiber 500 Mbps' => 'Fiber 500 Mbps', 'Fiber 1 Gbps' => 'Fiber 1 Gbps']"
                wire:model.live="plan"
            />

            <!-- Site Filter -->
            <x-ui.input.select
                name="site"
                placeholder="All Sites"
                :options="['' => 'All Sites', 'Downtown Hub' => 'Downtown Hub', 'Business Park' => 'Business Park', 'North Tower' => 'North Tower', 'South Station' => 'South Station', 'West Station' => 'West Station', 'East Center' => 'East Center']"
                wire:model.live="site"
            />

            <!-- Router Filter -->
            <x-ui.input.select
                name="router"
                placeholder="All Routers"
                :options="['' => 'All Routers', 'Mikrotik-01' => 'Mikrotik-01', 'Mikrotik-02' => 'Mikrotik-02', 'Mikrotik-03' => 'Mikrotik-03', 'Mikrotik-04' => 'Mikrotik-04', 'Mikrotik-05' => 'Mikrotik-05', 'Mikrotik-06' => 'Mikrotik-06', 'Cisco-01' => 'Cisco-01', 'Cisco-02' => 'Cisco-02', 'Cisco-03' => 'Cisco-03']"
                wire:model.live="router"
            />
        </div>
    </x-ui.card>

    <!-- Customers Table -->
    <x-ui.card :padding="'p-0'">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Plan
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Site / Router
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            IP Address
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Balance
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900">{{ $customer['name'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $customer['customer_code'] }}</span>
                                    <span class="text-xs text-gray-400">{{ $customer['email'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700">{{ $customer['plan'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-sm text-gray-700">
                                    <span>{{ $customer['site'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $customer['router'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 font-mono">{{ $customer['ip_address'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold {{ $customer['balance'] < 0 ? 'text-green-600' : ($customer['balance'] > 0 ? 'text-red-600' : 'text-gray-600') }}">
                                    ${{ number_format($customer['balance'], 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <x-ui.badge :status="$customer['status']">
                                    {{ ucfirst($customer['status']) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-500">{{ $customer['created_at'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('customers.show', $customer['id']) }}" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a href="{{ route('customers.edit', $customer['id']) }}" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <x-ui.dropdown align="right">
                                        <x-slot:trigger>
                                            <button class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                </svg>
                                            </button>
                                        </x-slot:trigger>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Suspend
                                            </span>
                                        </a>
                                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </span>
                                        </a>
                                    </x-ui.dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500">No customers found</p>
                                    <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                        Clear Filters
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($totalCustomers > $perPage)
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ ($this->getPage() - 1) * $perPage + 1 }} to {{ min($this->getPage() * $perPage, $totalCustomers) }} of {{ $totalCustomers }} customers
                </div>
                <div class="flex items-center gap-2">
                    {{ $customers->links() }}
                </div>
            </div>
        @endif
    </x-ui.card>
</div>
