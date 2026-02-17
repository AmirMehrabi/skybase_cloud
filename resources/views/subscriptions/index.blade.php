@extends('layouts.admin')

@section('title', 'Subscriptions')

@php
$subscriptions = [
    [
        'id' => 1,
        'subscription_code' => 'SUB-2024-001',
        'customer_name' => 'Acme Corporation',
        'customer_id' => 'CUST-001',
        'plan_name' => 'Fiber Business 100',
        'plan_price' => 89.99,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'site' => 'Downtown Office',
        'router' => 'MikroTik RouterBOARD-3011',
        'ip_address' => '192.168.1.100',
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'pppoe_username' => 'acme_corp_001',
        'start_date' => '2024-01-15',
        'end_date' => '2025-01-15',
        'next_billing_date' => '2025-03-15',
        'last_billing_date' => '2025-02-15',
        'auto_renew' => true,
        'contract_start' => '2024-01-15',
        'contract_end' => '2025-01-15',
        'installation_fee' => 150.00,
        'discount_percentage' => 10,
        'tax_percentage' => 8,
        'monthly_price' => 89.99,
        'total_price' => 97.19,
        'balance' => 0.00,
        'data_quota' => 1000,
        'data_used' => 650,
        'created_at' => '2024-01-10',
    ],
    [
        'id' => 2,
        'subscription_code' => 'SUB-2024-002',
        'customer_name' => 'Smith Residence',
        'customer_id' => 'CUST-002',
        'plan_name' => 'Home Fiber 50',
        'plan_price' => 49.99,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'site' => 'West Street',
        'router' => 'TP-Link ER605',
        'ip_address' => '192.168.1.101',
        'mac_address' => 'AA:BB:CC:DD:EE:02',
        'pppoe_username' => 'smith_res_002',
        'start_date' => '2024-02-01',
        'end_date' => '2025-02-01',
        'next_billing_date' => '2025-03-01',
        'last_billing_date' => '2025-02-01',
        'auto_renew' => true,
        'contract_start' => '2024-02-01',
        'contract_end' => '2025-02-01',
        'installation_fee' => 99.00,
        'discount_percentage' => 0,
        'tax_percentage' => 8,
        'monthly_price' => 49.99,
        'total_price' => 53.99,
        'balance' => 0.00,
        'data_quota' => 500,
        'data_used' => 320,
        'created_at' => '2024-01-28',
    ],
    [
        'id' => 3,
        'subscription_code' => 'SUB-2024-003',
        'customer_name' => 'Tech Startup Inc',
        'customer_id' => 'CUST-003',
        'plan_name' => 'Fiber Pro 500',
        'plan_price' => 299.99,
        'billing_cycle' => 'quarterly',
        'status' => 'suspended',
        'site' => 'Tech Park Building A',
        'router' => 'Cisco ISR 4431',
        'ip_address' => '192.168.1.102',
        'mac_address' => 'AA:BB:CC:DD:EE:03',
        'pppoe_username' => 'techstartup_003',
        'start_date' => '2024-01-10',
        'end_date' => '2025-01-10',
        'next_billing_date' => '2025-04-10',
        'last_billing_date' => '2025-01-10',
        'auto_renew' => true,
        'contract_start' => '2024-01-10',
        'contract_end' => '2025-01-10',
        'installation_fee' => 250.00,
        'discount_percentage' => 15,
        'tax_percentage' => 8,
        'monthly_price' => 299.99,
        'total_price' => 764.98,
        'balance' => 764.98,
        'data_quota' => 5000,
        'data_used' => 2100,
        'created_at' => '2024-01-05',
    ],
    [
        'id' => 4,
        'subscription_code' => 'SUB-2024-004',
        'customer_name' => 'Downtown Cafe',
        'customer_id' => 'CUST-004',
        'plan_name' => 'Business Fiber 200',
        'plan_price' => 149.99,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'site' => 'Main Street Location',
        'router' => 'Ubiquiti EdgeRouter 12',
        'ip_address' => '192.168.1.103',
        'mac_address' => 'AA:BB:CC:DD:EE:04',
        'pppoe_username' => 'downtowncafe_004',
        'start_date' => '2024-03-01',
        'end_date' => '2025-03-01',
        'next_billing_date' => '2025-03-01',
        'last_billing_date' => '2025-02-01',
        'auto_renew' => true,
        'contract_start' => '2024-03-01',
        'contract_end' => '2025-03-01',
        'installation_fee' => 199.00,
        'discount_percentage' => 5,
        'tax_percentage' => 8,
        'monthly_price' => 149.99,
        'total_price' => 156.59,
        'balance' => 0.00,
        'data_quota' => 2000,
        'data_used' => 1450,
        'created_at' => '2024-02-25',
    ],
    [
        'id' => 5,
        'subscription_code' => 'SUB-2023-098',
        'customer_name' => 'Johnson Family',
        'customer_id' => 'CUST-005',
        'plan_name' => 'Home Fiber 25',
        'plan_price' => 34.99,
        'billing_cycle' => 'monthly',
        'status' => 'pending',
        'site' => 'Oak Lane Residence',
        'router' => 'MikroTik hAP ac²',
        'ip_address' => '192.168.1.104',
        'mac_address' => 'AA:BB:CC:DD:EE:05',
        'pppoe_username' => 'johnson_family_005',
        'start_date' => '2025-02-20',
        'end_date' => '2026-02-20',
        'next_billing_date' => '2025-03-20',
        'last_billing_date' => null,
        'auto_renew' => true,
        'contract_start' => '2025-02-20',
        'contract_end' => '2026-02-20',
        'installation_fee' => 79.00,
        'discount_percentage' => 0,
        'tax_percentage' => 8,
        'monthly_price' => 34.99,
        'total_price' => 37.79,
        'balance' => 0.00,
        'data_quota' => 300,
        'data_used' => 0,
        'created_at' => '2025-02-15',
    ],
    [
        'id' => 6,
        'subscription_code' => 'SUB-2023-085',
        'customer_name' => 'Global Logistics Ltd',
        'customer_id' => 'CUST-006',
        'plan_name' => 'Enterprise Fiber 1G',
        'plan_price' => 899.99,
        'billing_cycle' => 'yearly',
        'status' => 'active',
        'site' => 'Industrial District Hub',
        'router' => 'Juniper MX204',
        'ip_address' => '192.168.1.105',
        'mac_address' => 'AA:BB:CC:DD:EE:06',
        'pppoe_username' => 'globallogistics_006',
        'start_date' => '2023-06-01',
        'end_date' => '2024-06-01',
        'next_billing_date' => '2025-06-01',
        'last_billing_date' => '2024-06-01',
        'auto_renew' => true,
        'contract_start' => '2023-06-01',
        'contract_end' => '2024-06-01',
        'installation_fee' => 500.00,
        'discount_percentage' => 20,
        'tax_percentage' => 8,
        'monthly_price' => 899.99,
        'total_price' => 7799.91,
        'balance' => 0.00,
        'data_quota' => 10000,
        'data_used' => 7200,
        'created_at' => '2023-05-25',
    ],
    [
        'id' => 7,
        'subscription_code' => 'SUB-2023-072',
        'customer_name' => 'Mountain View Hotel',
        'customer_id' => 'CUST-007',
        'plan_name' => 'Business Fiber 500',
        'plan_price' => 399.99,
        'billing_cycle' => 'quarterly',
        'status' => 'cancelled',
        'site' => 'Highland Resort',
        'router' => 'Cisco Catalyst 9200',
        'ip_address' => '192.168.1.106',
        'mac_address' => 'AA:BB:CC:DD:EE:07',
        'pppoe_username' => 'mountainview_007',
        'start_date' => '2023-05-15',
        'end_date' => '2024-05-15',
        'next_billing_date' => null,
        'last_billing_date' => '2024-02-15',
        'auto_renew' => false,
        'contract_start' => '2023-05-15',
        'contract_end' => '2024-05-15',
        'installation_fee' => 350.00,
        'discount_percentage' => 10,
        'tax_percentage' => 8,
        'monthly_price' => 399.99,
        'total_price' => 1031.98,
        'balance' => 0.00,
        'data_quota' => 8000,
        'data_used' => 0,
        'created_at' => '2023-05-10',
    ],
    [
        'id' => 8,
        'subscription_code' => 'SUB-2023-065',
        'customer_name' => 'Riverside Apartments',
        'customer_id' => 'CUST-008',
        'plan_name' => 'Multi-Unit Fiber 100',
        'plan_price' => 599.99,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'site' => 'River Bend Complex',
        'router' => 'Ubiquiti UniFi Dream Machine',
        'ip_address' => '192.168.1.107',
        'mac_address' => 'AA:BB:CC:DD:EE:08',
        'pppoe_username' => 'riverside_apt_008',
        'start_date' => '2023-04-01',
        'end_date' => '2024-04-01',
        'next_billing_date' => '2025-03-01',
        'last_billing_date' => '2025-02-01',
        'auto_renew' => true,
        'contract_start' => '2023-04-01',
        'contract_end' => '2024-04-01',
        'installation_fee' => 450.00,
        'discount_percentage' => 12,
        'tax_percentage' => 8,
        'monthly_price' => 599.99,
        'total_price' => 575.99,
        'balance' => 1151.98,
        'data_quota' => 3000,
        'data_used' => 1890,
        'created_at' => '2023-03-28',
    ],
    [
        'id' => 9,
        'subscription_code' => 'SUB-2023-055',
        'customer_name' => 'City Library',
        'customer_id' => 'CUST-009',
        'plan_name' => 'Non-Profit Fiber 200',
        'plan_price' => 99.99,
        'billing_cycle' => 'yearly',
        'status' => 'expired',
        'site' => 'Public Library Branch',
        'router' => 'MikroTik CCR1009',
        'ip_address' => '192.168.1.108',
        'mac_address' => 'AA:BB:CC:DD:EE:09',
        'pppoe_username' => 'citylibrary_009',
        'start_date' => '2023-03-01',
        'end_date' => '2024-03-01',
        'next_billing_date' => null,
        'last_billing_date' => '2024-03-01',
        'auto_renew' => false,
        'contract_start' => '2023-03-01',
        'contract_end' => '2024-03-01',
        'installation_fee' => 0.00,
        'discount_percentage' => 50,
        'tax_percentage' => 0,
        'monthly_price' => 99.99,
        'total_price' => 599.94,
        'balance' => 0.00,
        'data_quota' => 2000,
        'data_used' => 0,
        'created_at' => '2023-02-25',
    ],
    [
        'id' => 10,
        'subscription_code' => 'SUB-2024-010',
        'customer_name' => 'Elena Rodriguez',
        'customer_id' => 'CUST-010',
        'plan_name' => 'Home Fiber 100',
        'plan_price' => 69.99,
        'billing_cycle' => 'monthly',
        'status' => 'active',
        'site' => 'Sunset Boulevard',
        'router' => 'TP-Link ER707-M2',
        'ip_address' => '192.168.1.109',
        'mac_address' => 'AA:BB:CC:DD:EE:10',
        'pppoe_username' => 'rodriguez_elena_010',
        'start_date' => '2024-05-01',
        'end_date' => '2025-05-01',
        'next_billing_date' => '2025-03-01',
        'last_billing_date' => '2025-02-01',
        'auto_renew' => true,
        'contract_start' => '2024-05-01',
        'contract_end' => '2025-05-01',
        'installation_fee' => 99.00,
        'discount_percentage' => 0,
        'tax_percentage' => 8,
        'monthly_price' => 69.99,
        'total_price' => 75.59,
        'balance' => 0.00,
        'data_quota' => 1000,
        'data_used' => 820,
        'created_at' => '2024-04-28',
    ],
];

$totalSubscriptions = count($subscriptions);
$activeCount = count(array_filter($subscriptions, fn($s) => $s['status'] === 'active'));
$suspendedCount = count(array_filter($subscriptions, fn($s) => $s['status'] === 'suspended'));
$expiringSoonCount = count(array_filter($subscriptions, fn($s) => strtotime($s['contract_end']) - strtotime('2025-02-17') > 0 && strtotime($s['contract_end']) - strtotime('2025-02-17') < 30 * 24 * 60 * 60));
@endphp

@php
function getStatusBadgeClass($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'suspended' => 'bg-red-100 text-red-800 border-red-200',
        'cancelled' => 'bg-gray-100 text-gray-800 border-gray-200',
        'expired' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}
@endphp



@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscriptions</h1>
            <p class="text-sm text-gray-500 mt-1">Manage customer service plans and billing</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Subscription
            </a>

            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Export
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
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
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as CSV</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as PDF</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Export as Excel</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Subscriptions -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Subscriptions</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalSubscriptions }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Active</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $activeCount }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 border border-green-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Suspended -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Suspended</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $suspendedCount }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Expiring Soon -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Expiring Soon</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $expiringSoonCount }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-yellow-50 text-yellow-600 border border-yellow-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
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
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" placeholder="Subscription code, customer name..." class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <!-- Plan -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Plan</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Plans</option>
                        <option value="fiber-home">Home Fiber 25</option>
                        <option value="fiber-home-50">Home Fiber 50</option>
                        <option value="fiber-home-100">Home Fiber 100</option>
                        <option value="fiber-business">Business Fiber 200</option>
                        <option value="fiber-pro">Fiber Pro 500</option>
                        <option value="fiber-enterprise">Enterprise Fiber 1G</option>
                    </select>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Billing Cycle</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Cycles</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <!-- Site -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Site</label>
                    <select class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Sites</option>
                        <option value="downtown">Downtown Office</option>
                        <option value="west-street">West Street</option>
                        <option value="tech-park">Tech Park</option>
                        <option value="main-street">Main Street</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Contract End Date</label>
                    <input type="date" class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Clear Filters Button -->
                <div class="lg:col-span-6 flex items-center justify-end">
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

    <!-- Subscription Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscription</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Billing Cycle</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Monthly Price</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data Usage</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Next Billing</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Auto Renew</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($subscriptions as $sub)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('subscriptions.show', $sub['id']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                {{ $sub['subscription_code'] }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $sub['customer_name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $sub['customer_id'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $sub['plan_name'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                {{ ucfirst($sub['billing_cycle']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${{ number_format($sub['monthly_price'], 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="w-full">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-gray-600">{{ number_format($sub['data_used']) }} GB</span>
                                    <span class="text-gray-500">/ {{ number_format($sub['data_quota']) }} GB</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    @php
                                        $usagePercent = min(($sub['data_used'] / $sub['data_quota']) * 100, 100);
                                        $barColor = $usagePercent > 90 ? 'bg-red-500' : ($usagePercent > 70 ? 'bg-yellow-500' : 'bg-green-500');
                                    @endphp
                                    <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $usagePercent }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($sub['next_billing_date'])->format('M d, Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($sub['status']) }}">
                                {{ ucfirst($sub['status']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($sub['auto_renew'])
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
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
                                    <a href="{{ route('subscriptions.show', $sub['id']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View Details</a>
                                    <a href="{{ route('subscriptions.edit', $sub['id']) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                                    @if($sub['status'] === 'active')
                                        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Suspend</a>
                                    @elseif($sub['status'] === 'suspended')
                                        <a href="#" class="block px-4 py-2 text-sm text-green-600 hover:bg-gray-50">Activate</a>
                                    @endif
                                    <div class="border-t border-gray-200 my-1"></div>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Generate Invoice</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Cancel Subscription</a>
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

<!-- Delete Confirmation Modal -->
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
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Cancel Subscription</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to cancel this subscription? This action cannot be undone. The service will be terminated immediately.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" @click="show = false" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Cancel Subscription</button>
                    <button type="button" @click="show = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Go Back</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
