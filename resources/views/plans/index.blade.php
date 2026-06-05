@extends('layouts.admin')

@section('title', 'Plans')

@php
$totalPlans = $plans->count();
$activeCount = $plans->where('status', 'active')->count();
$totalSubscribers = $plans->sum('subscribers');

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
        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('plans.export') }}">
                @csrf
                <input type="hidden" name="search" :value="search">
                <input type="hidden" name="status" :value="filterStatus">
                <input type="hidden" name="type" :value="filterType">
                <input type="hidden" name="category" :value="filterCategory">
                <input type="hidden" name="billing_cycle" :value="filterBillingCycle">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </button>
            </form>
            <button type="button" @click="importModal.show = true" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                </svg>
                Import
            </button>
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
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.action-icon x-bind:href="`/plans/${plan.id}`" icon="view" label="View" />
                                    <x-ui.action-icon x-bind:href="`/plans/${plan.id}/edit`" icon="edit" label="Edit" />
                                    <x-ui.action-icon as="button" icon="delete" label="Delete" @click="confirmDelete(plan)" />
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <x-ui.action-icon as="button" icon="more" label="More actions" @click="open = !open" @click.outside="open = false" />

                                        <div x-show="open"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             class="absolute right-0 mt-2 w-48 rounded-xl shadow-lg bg-white border border-gray-200 py-1 z-50"
                                             style="display: none;">
                                            <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Clone Plan</button>
                                            <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View Subscribers</button>
                                        </div>
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

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Import / Export Activity</h2>
                <p class="mt-1 text-xs text-gray-500">Queued imports, exports, downloads, and row-level reports.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="fetchImportExportRuns()" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50">Refresh</button>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Run</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Rows</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Results</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-if="importExportRuns.length === 0">
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No import/export runs yet.</td>
                        </tr>
                    </template>
                    <template x-for="run in importExportRuns" :key="run.id">
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium text-gray-900" x-text="`${capitalize(run.direction)} #${run.id}`"></div>
                                <div class="text-xs text-gray-500" x-text="run.created_at"></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold" :class="runStatusClass(run.status)" x-text="capitalize(run.status)"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700" x-text="`${run.processed_rows} / ${run.total_rows}`"></td>
                            <td class="px-4 py-3 text-sm text-gray-700" x-text="`Created ${run.created_count}, updated ${run.updated_count}, failed ${run.failed_count}`"></td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="run.report_url" class="font-medium text-blue-600 hover:text-blue-800">Report</a>
                                    <template x-if="run.download_url">
                                        <a :href="run.download_url" class="font-medium text-green-700 hover:text-green-800">Download</a>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" x-show="importExportPagination.total > 0" style="display: none;">
            <p class="text-sm text-gray-700">
                Showing <span class="font-medium" x-text="importExportPagination.from"></span> to <span class="font-medium" x-text="importExportPagination.to"></span> of <span class="font-medium" x-text="importExportPagination.total"></span> results
            </p>
            <nav class="relative z-0 inline-flex flex-wrap rounded-md shadow-sm -space-x-px">
                <button @click="prevImportExportPage()" :disabled="importExportPagination.current_page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                <template x-for="(page, index) in importExportPaginationPages" :key="index">
                    <span x-show="page === '...'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500">...</span>
                    <button
                        x-show="page !== '...'"
                        @click="goToImportExportPage(page)"
                        :class="importExportPagination.current_page === page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'"
                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                        x-text="page"
                    ></button>
                </template>
                <button @click="nextImportExportPage()" :disabled="importExportPagination.current_page === importExportPagination.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </nav>
        </div>
    </div>

    <x-ui.delete-modal
        title="Delete Plan"
        name="deleteModal.plan?.name"
        confirm-action="deletePlan()"
    />

    <div x-show="importModal.show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" @click.outside="importModal.show = false">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Import Plans</h2>
                    <p class="mt-1 text-sm text-gray-500">Upload the same XLSX template produced by Export. Matching internal names are updated.</p>
                </div>
                <button type="button" @click="importModal.show = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('plans.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <x-input.file name="file" label="XLSX file" accept=".xlsx,.xls" required />
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    Rows with validation errors are skipped and listed in the report.
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="importModal.show = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Queue Import</button>
                </div>
            </form>
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
        plans: @js($plans->toArray()),
        deleteModal: {
            show: false,
            plan: null,
            deleting: false,
        },
        importModal: {
            show: false,
        },
        importExportRuns: [],
        importExportPagination: {
            current_page: 1,
            last_page: 1,
            per_page: 5,
            total: 0,
            from: 0,
            to: 0
        },
        importExportTimer: null,

        init() {
            this.fetchImportExportRuns();
            this.importExportTimer = window.setInterval(() => this.fetchImportExportRuns(), 10000);
        },

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
        },
        runStatusClass(status) {
            const classes = {
                queued: 'bg-slate-100 text-slate-700 border-slate-200',
                processing: 'bg-blue-100 text-blue-700 border-blue-200',
                completed: 'bg-green-100 text-green-700 border-green-200',
                failed: 'bg-red-100 text-red-700 border-red-200',
            };

            return classes[status] || 'bg-gray-100 text-gray-700 border-gray-200';
        },
        async fetchImportExportRuns() {
            try {
                const response = await fetch(`{{ route('plans.import-export-runs') }}?page=${this.importExportPagination.current_page}`);
                const data = await response.json();
                this.importExportRuns = data.runs;
                this.importExportPagination = data.pagination;
            } catch (error) {
                console.error('Error fetching import/export runs:', error);
            }
        },
        goToImportExportPage(page) {
            if (page < 1 || page > this.importExportPagination.last_page || page === this.importExportPagination.current_page) {
                return;
            }

            this.importExportPagination.current_page = page;
            this.fetchImportExportRuns();
        },
        prevImportExportPage() {
            this.goToImportExportPage(this.importExportPagination.current_page - 1);
        },
        nextImportExportPage() {
            this.goToImportExportPage(this.importExportPagination.current_page + 1);
        },
        get importExportPaginationPages() {
            const totalPages = this.importExportPagination.last_page;
            const currentPage = this.importExportPagination.current_page;

            if (totalPages <= 7) {
                return Array.from({ length: totalPages }, (_, index) => index + 1);
            }

            const pages = [1];
            const start = Math.max(2, currentPage - 1);
            const end = Math.min(totalPages - 1, currentPage + 1);

            if (start > 2) {
                pages.push('...');
            }

            for (let page = start; page <= end; page++) {
                pages.push(page);
            }

            if (end < totalPages - 1) {
                pages.push('...');
            }

            pages.push(totalPages);

            return pages;
        },
        confirmDelete(plan) {
            this.deleteModal.plan = plan;
            this.deleteModal.show = true;
        },
        async deletePlan() {
            if (!this.deleteModal.plan) return;

            this.deleteModal.deleting = true;
            try {
                const response = await fetch(`/plans/${this.deleteModal.plan.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.plans = this.plans.filter((plan) => plan.id !== this.deleteModal.plan.id);
                    this.deleteModal.show = false;
                } else {
                    alert('Error deleting plan. Please try again.');
                }
            } finally {
                this.deleteModal.deleting = false;
            }
        },
    };
}
</script>
@endsection
