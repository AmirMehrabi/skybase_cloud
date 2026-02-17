@extends('layouts.admin')

@section('title', 'Plans')

@php
$plans = [
    [
        'id' => 1,
        'name' => 'Home 10 Mbps',
        'internal_name' => 'home_10',
        'description' => 'Basic residential internet for light usage',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'pppoe',
        'category' => 'Residential',
        'download_speed' => 10,
        'upload_speed' => 5,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 200,
        'data_unit' => 'GB',
        'unlimited' => false,
        'price' => 15.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 89,
    ],
    [
        'id' => 2,
        'name' => 'Home 50 Mbps',
        'internal_name' => 'home_50',
        'description' => 'Standard residential internet plan',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'pppoe',
        'category' => 'Residential',
        'download_speed' => 50,
        'upload_speed' => 10,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 500,
        'data_unit' => 'GB',
        'unlimited' => false,
        'price' => 25.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 124,
    ],
    [
        'id' => 3,
        'name' => 'Home 100 Mbps',
        'internal_name' => 'home_100',
        'description' => 'High-speed residential for heavy usage',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'pppoe',
        'category' => 'Residential',
        'download_speed' => 100,
        'upload_speed' => 20,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 1000,
        'data_unit' => 'GB',
        'unlimited' => false,
        'price' => 45.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 203,
    ],
    [
        'id' => 4,
        'name' => 'Business 50 Mbps',
        'internal_name' => 'business_50',
        'description' => 'Small business internet solution',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'pppoe',
        'category' => 'Business',
        'download_speed' => 50,
        'upload_speed' => 50,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 1000,
        'data_unit' => 'GB',
        'unlimited' => false,
        'price' => 79.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 56,
    ],
    [
        'id' => 5,
        'name' => 'Business 200 Mbps',
        'internal_name' => 'business_200',
        'description' => 'High-speed internet for growing businesses',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'pppoe',
        'category' => 'Business',
        'download_speed' => 200,
        'upload_speed' => 100,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 3000,
        'data_unit' => 'GB',
        'unlimited' => false,
        'price' => 149.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 78,
    ],
    [
        'id' => 6,
        'name' => 'Enterprise Fiber',
        'internal_name' => 'enterprise_fiber',
        'description' => 'Dedicated fiber connection for enterprises',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'fiber',
        'category' => 'Enterprise',
        'download_speed' => 1000,
        'upload_speed' => 1000,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 0,
        'data_unit' => 'GB',
        'unlimited' => true,
        'price' => 499.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 23,
    ],
    [
        'id' => 7,
        'name' => 'Unlimited Premium',
        'internal_name' => 'unlimited_premium',
        'description' => 'Unlimited data with premium speeds',
        'status' => 'active',
        'visibility' => 'public',
        'type' => 'pppoe',
        'category' => 'Residential',
        'download_speed' => 200,
        'upload_speed' => 50,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 0,
        'data_unit' => 'GB',
        'unlimited' => true,
        'price' => 89.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 167,
    ],
    [
        'id' => 8,
        'name' => 'Starter Plan',
        'internal_name' => 'starter',
        'description' => 'Budget-friendly basic internet',
        'status' => 'inactive',
        'visibility' => 'private',
        'type' => 'hotspot',
        'category' => 'Residential',
        'download_speed' => 5,
        'upload_speed' => 2,
        'bandwidth_unit' => 'Mbps',
        'data_limit' => 100,
        'data_unit' => 'GB',
        'unlimited' => false,
        'price' => 10.00,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'subscribers' => 12,
    ],
];

$totalPlans = count($plans);
$activeCount = count(array_filter($plans, fn($p) => $p['status'] === 'active'));
$totalSubscribers = array_sum(array_column($plans, 'subscribers'));

function getStatusBadgeClass($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'inactive' => 'bg-gray-100 text-gray-800 border-gray-200',
        'archived' => 'bg-red-100 text-red-800 border-red-200',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}
@endphp

@section('content')
<div x-data="plansModule()" class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Plans</h1>
            <p class="text-sm text-gray-500 mt-1">Manage service plans and pricing</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('plans.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Plan
            </a>
        </div>
    </div>

    <!-- Summary Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Plans -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Plans</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalPlans }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
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

        <!-- Total Subscribers -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Subscribers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalSubscribers }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Categories</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">3</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-yellow-50 text-yellow-600 border border-yellow-100 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
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

            <div x-show="showFilters" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" x-model="search" placeholder="Plan name, internal name..." class="block w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select x-model="filterStatus" class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Service Type</label>
                    <select x-model="filterType" class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="pppoe">PPPoE</option>
                        <option value="hotspot">Hotspot</option>
                        <option value="fiber">Fiber</option>
                        <option value="static">Static IP</option>
                        <option value="dhcp">DHCP</option>
                        <option value="wireless">Wireless</option>
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Category</label>
                    <select x-model="filterCategory" class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Categories</option>
                        <option value="Residential">Residential</option>
                        <option value="Business">Business</option>
                        <option value="Enterprise">Enterprise</option>
                    </select>
                </div>

                <!-- Billing Cycle -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Billing Cycle</label>
                    <select x-model="filterBillingCycle" class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Cycles</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>

                <!-- Clear Filters Button -->
                <div class="flex items-end">
                    <button @click="search = ''; filterStatus = ''; filterType = ''; filterCategory = ''; filterBillingCycle = ''" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Plans Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Speed</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data Limit</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Billing Cycle</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscribers</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="plan in filteredPlans" :key="plan.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a :href="`/plans/${plan.id}`" class="text-sm font-medium text-blue-600 hover:text-blue-800" x-text="plan.name"></a>
                                <div class="text-xs text-gray-500" x-text="plan.internal_name"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200" x-text="plan.type.toUpperCase()"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="`${plan.download_speed} / ${plan.upload_speed} ${plan.bandwidth_unit}`"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <template x-if="plan.unlimited">
                                        <span class="text-green-600 font-medium">Unlimited</span>
                                    </template>
                                    <template x-if="!plan.unlimited">
                                        <span x-text="`${plan.data_limit} ${plan.data_unit}`"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="formatCurrency(plan.price, plan.currency)"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200" x-text="capitalize(plan.billing_cycle)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="plan.subscribers"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border" :class="getStatusBadgeClass(plan.status)" x-text="capitalize(plan.status)"></span>
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
                                        <a :href="`/plans/${plan.id}`" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View</a>
                                        <a :href="`/plans/${plan.id}/edit`" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Clone Plan</button>
                                        <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View Subscribers</button>
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <button class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Delete</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty State -->
            <div x-show="filteredPlans.length === 0" class="text-center py-12" style="display: none;">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No plans found</h3>
                <p class="text-sm text-gray-500 mb-4">Create your first plan to get started</p>
                <a href="{{ route('plans.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Plan
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function plansModule() {
    return {
        search: '',
        filterStatus: '',
        filterType: '',
        filterCategory: '',
        filterBillingCycle: '',
        plans: @js($plans),

        get filteredPlans() {
            return this.plans.filter(plan => {
                const matchesSearch = !this.search ||
                    plan.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    plan.internal_name.toLowerCase().includes(this.search.toLowerCase());

                const matchesStatus = !this.filterStatus || plan.status === this.filterStatus;
                const matchesType = !this.filterType || plan.type === this.filterType;
                const matchesCategory = !this.filterCategory || plan.category === this.filterCategory;
                const matchesBillingCycle = !this.filterBillingCycle || plan.billing_cycle === this.filterBillingCycle;

                return matchesSearch && matchesStatus && matchesType && matchesCategory && matchesBillingCycle;
            });
        },

        formatCurrency(price, currency) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(price);
        },

        capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        getStatusBadgeClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800 border-green-200',
                'inactive': 'bg-gray-100 text-gray-800 border-gray-200',
                'archived': 'bg-red-100 text-red-800 border-red-200',
            };
            return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };
}
</script>
@endsection
