@extends('layouts.admin')

@section('title', 'Subscriptions')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="subscriptionsIndex()">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscriptions</h1>
            <p class="text-sm text-gray-500 mt-1">Manage customer subscriptions and services</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('subscriptions.export') }}">
                @csrf
                <input type="hidden" name="search" :value="filters.search">
                <input type="hidden" name="status" :value="filters.status">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export
                </button>
            </form>
            <button type="button" @click="importModal.show = true" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                </svg>
                Import
            </button>
            <button type="button" @click="ipAdjustmentModal.show = true" class="hidden" aria-hidden="true" tabindex="-1">
                Import IP Adjustments
            </button>
            <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Subscription
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.total"></p>
                </div>
                <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Active</p>
                    <p class="text-2xl font-bold text-green-600" x-text="stats.active"></p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Online</p>
                    <p class="text-2xl font-bold text-green-600" x-text="stats.online"></p>
                </div>
                <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600" x-text="stats.pending"></p>
                </div>
                <div class="h-12 w-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Suspended</p>
                    <p class="text-2xl font-bold text-red-600" x-text="stats.suspended"></p>
                </div>
                <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm">
        <!-- Filters -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" x-model="filters.search" @input="clearSelection(); debounceFetch()" placeholder="Search by customer, email, or subscription code..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border">
                </div>
                <div class="sm:w-48">
                    <select x-model="filters.status" @change="clearSelection(); fetchData()" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2 px-3 border bg-white">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" x-show="pagination.total > 0" style="display: none;">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <p class="text-sm font-semibold text-gray-900">
                    <span x-text="selectedSubscriptionCount"></span> subscription<span x-show="selectedSubscriptionCount !== 1" style="display: none;">s</span> selected
                </p>
                <p class="text-xs text-gray-500">
                    <span x-show="selectionMode === 'all'" style="display: none;">All filtered subscriptions are selected except the ones you excluded on the current pages.</span>
                    <span x-show="selectionMode !== 'all'" style="display: none;">Selection stays limited to the rows you checked.</span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="selectAllMatching()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    Select all matching
                </button>
                <button type="button" @click="clearSelection()" :disabled="!hasSelectedSubscriptions" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear selection
                </button>
                <button type="button" @click="bulkDeleteSelected()" :disabled="!hasSelectedSubscriptions || bulkDeleting" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <span x-show="!bulkDeleting">Delete selected</span>
                    <span x-show="bulkDeleting" style="display: none;">Queueing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" :checked="allVisibleSubscriptionsSelected" @change="toggleVisibleSelection($event.target.checked)">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Router</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Connection Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="12" class="px-6 py-12 text-center text-gray-500">
                                Loading...
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && subscriptions.length === 0">
                        <tr>
                            <td colspan="12" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No subscriptions</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating a new subscription.</p>
                                <div class="mt-6">
                                    <a href="{{ route('subscriptions.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Create Subscription
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-for="subscription in subscriptions" :key="subscription.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 align-top">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" :checked="isSubscriptionSelected(subscription.id)" @change="toggleSubscriptionSelection(subscription.id, $event.target.checked)">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="subscription.subscription_code"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="subscription.customer_name"></div>
                                <div class="text-sm text-gray-500" x-text="subscription.customer_email"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="subscription.plan"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="subscription.router"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-1">
                                <button
                                    type="button"
                                    class="w-full inline-flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left text-sm font-mono text-gray-900 transition-colors hover:bg-gray-100"
                                    @click="copyCredential(`username-${subscription.id}`, subscription.pppoe_username)"
                                >
                                    <span x-text="subscription.pppoe_username || '—'"></span>
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-9 8h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                <p class="text-xs text-green-600" x-show="copiedCredential === `username-${subscription.id}`" x-transition>Copied</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-1">
                                <button
                                    type="button"
                                    class="w-full inline-flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left text-sm font-mono text-gray-900 transition-colors hover:bg-gray-100"
                                    @click="togglePassword(subscription.id, subscription.pppoe_password)"
                                >
                                    <span x-text="visiblePasswords[subscription.id] ? (subscription.pppoe_password || '—') : maskPassword(subscription.pppoe_password)"></span>
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="!visiblePasswords[subscription.id]" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path x-show="!visiblePasswords[subscription.id]" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        <path x-show="visiblePasswords[subscription.id]" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.453 10.453 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.48 10.48 0 012.119-3.675m2.303-2.188A9.961 9.961 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.451 10.451 0 01-4.184 5.216M15 12a3 3 0 01-3 3m-2.121-.879A3 3 0 009 12"></path>
                                    </svg>
                                </button>
                                <p class="text-xs text-green-600" x-show="copiedCredential === `password-${subscription.id}`" x-transition>Copied</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="'$' + subscription.total_price"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': subscription.status === 'active',
                                          'bg-yellow-100 text-yellow-800': subscription.status === 'pending',
                                          'bg-red-100 text-red-800': subscription.status === 'suspended',
                                          'bg-gray-100 text-gray-800': subscription.status === 'cancelled'
                                      }" x-text="subscription.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': subscription.connection_status === 'online',
                                        'bg-red-100 text-red-800': subscription.connection_status === 'offline',
                                        'bg-gray-100 text-gray-800': !subscription.connection_status
                                    }"
                                    x-text="subscription.connection_status || 'N/A'"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="subscription.activation_date || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.action-icon x-bind:href="'/subscriptions/' + subscription.id" icon="view" label="View" />
                                    <x-ui.action-icon x-bind:href="'/subscriptions/' + subscription.id + '/edit'" icon="edit" label="Edit" />
                                    <x-ui.action-icon as="button" icon="suspend" label="Suspend" x-show="subscription.status === 'active'" @click="suspendSubscription(subscription)" />
                                    <x-ui.action-icon as="button" icon="delete" label="Delete" @click="confirmDelete(subscription)" />
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium" x-text="pagination.from"></span> to <span class="font-medium" x-text="pagination.to"></span> of <span class="font-medium" x-text="pagination.total"></span> results
                </p>
                <nav class="relative z-0 inline-flex flex-wrap rounded-md shadow-sm -space-x-px">
                    <button @click="prevPage()" :disabled="pagination.current_page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <template x-for="(page, index) in paginationPages" :key="index">
                        <span x-show="page === '...'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500">...</span>
                        <button
                            x-show="page !== '...'"
                            @click="goToPage(page)"
                            :class="pagination.current_page === page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-300'"
                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                            x-text="page"
                        ></button>
                    </template>
                    <button @click="nextPage()" :disabled="pagination.current_page === pagination.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </nav>
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
                                <div class="text-xs text-gray-500" x-text="run.original_filename || run.created_at"></div>
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
        title="Delete Subscription"
        name="deleteModal.subscription?.subscription_code"
        confirm-action="deleteSubscription()"
    />

    <div x-show="importModal.show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" @click.outside="importModal.show = false">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Import Customers and Subscriptions</h2>
                    <p class="mt-1 text-sm text-gray-500">Upload the same XLSX template produced by Export. Matching customer and subscription codes are updated.</p>
                </div>
                <button type="button" @click="importModal.show = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('subscriptions.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <x-input.file name="file" label="XLSX file" accept=".xlsx,.xls" required />
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    Each row imports customer fields and one subscription. Rows with validation errors are skipped and listed in the report.
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="importModal.show = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Queue Import</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="ipAdjustmentModal.show" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" @click.outside="ipAdjustmentModal.show = false">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Import IP Adjustments</h2>
                    <p class="mt-1 text-sm text-gray-500">Upload an XLSX file with `username` and `ip_address`. The first IP becomes the primary address and the rest are routes.</p>
                </div>
                <button type="button" @click="ipAdjustmentModal.show = false" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('subscriptions.import-ip-addresses') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <x-input.file name="file" label="XLSX file" accept=".xlsx,.xls" required />
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    Existing IPs will be released and reassigned to the imported subscription. Route rows can include CIDR values such as <span class="font-mono">10.0.0.2/29</span>.
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="ipAdjustmentModal.show = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Queue Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function subscriptionsIndex() {
    return {
        subscriptions: [],
        stats: { total: 0, active: 0, online: 0, pending: 0, suspended: 0, cancelled: 0 },
        visiblePasswords: {},
        copiedCredential: null,
        bulkDeleting: false,
        selectedIds: [],
        excludedIds: [],
        selectionMode: 'selected',
        filters: {
            search: '',
            status: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 100,
            total: 0,
            from: 0,
            to: 0
        },
        loading: false,
        debounceTimer: null,
        deleteModal: {
            show: false,
            subscription: null,
            deleting: false
        },
        importModal: {
            show: false,
        },
        ipAdjustmentModal: {
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
            this.fetchStats();
            this.fetchData();
            this.fetchImportExportRuns();
            this.importExportTimer = window.setInterval(() => this.fetchImportExportRuns(), 10000);
        },

        async fetchStats() {
            try {
                const response = await fetch('{{ route('subscriptions.stats') }}');
                this.stats = await response.json();
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page
                });

                const response = await fetch(`{{ route('subscriptions.data') }}?${params}`);
                const data = await response.json();

                this.subscriptions = data.subscriptions;
                this.pagination = data.pagination;
            } catch (error) {
                console.error('Error fetching subscriptions:', error);
            } finally {
                this.loading = false;
            }
        },
        clearSelection() {
            this.selectionMode = 'selected';
            this.selectedIds = [];
            this.excludedIds = [];
        },
        selectAllMatching() {
            this.selectionMode = 'all';
            this.selectedIds = [];
            this.excludedIds = [];
        },
        isSubscriptionSelected(subscriptionId) {
            if (this.selectionMode === 'all') {
                return !this.excludedIds.includes(subscriptionId);
            }

            return this.selectedIds.includes(subscriptionId);
        },
        toggleVisibleSelection(checked) {
            const visibleIds = this.subscriptions.map((subscription) => subscription.id);

            if (this.selectionMode === 'all') {
                if (checked) {
                    this.excludedIds = this.excludedIds.filter((id) => !visibleIds.includes(id));
                } else {
                    visibleIds.forEach((id) => {
                        if (!this.excludedIds.includes(id)) {
                            this.excludedIds.push(id);
                        }
                    });
                }

                return;
            }

            if (checked) {
                this.selectedIds = Array.from(new Set([...this.selectedIds, ...visibleIds]));
            } else {
                this.selectedIds = this.selectedIds.filter((id) => !visibleIds.includes(id));
            }
        },
        toggleSubscriptionSelection(subscriptionId, checked) {
            if (this.selectionMode === 'all') {
                if (checked) {
                    this.excludedIds = this.excludedIds.filter((id) => id !== subscriptionId);
                } else if (!this.excludedIds.includes(subscriptionId)) {
                    this.excludedIds.push(subscriptionId);
                }

                return;
            }

            if (checked) {
                if (!this.selectedIds.includes(subscriptionId)) {
                    this.selectedIds.push(subscriptionId);
                }
            } else {
                this.selectedIds = this.selectedIds.filter((id) => id !== subscriptionId);
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page || page === this.pagination.current_page) {
                return;
            }

            this.pagination.current_page = page;
            this.fetchData();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        get paginationPages() {
            const totalPages = this.pagination.last_page;
            const currentPage = this.pagination.current_page;

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
        async fetchImportExportRuns() {
            try {
                const response = await fetch(`{{ route('subscriptions.import-export-runs') }}?page=${this.importExportPagination.current_page}`);
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

        maskPassword(password) {
            return password ? '••••••••' : '—';
        },

        async copyCredential(key, value) {
            if (!value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(value);
                this.copiedCredential = key;

                window.setTimeout(() => {
                    if (this.copiedCredential === key) {
                        this.copiedCredential = null;
                    }
                }, 1200);
            } catch (error) {
                console.error('Unable to copy credential:', error);
            }
        },

        togglePassword(subscriptionId, password) {
            if (!password) {
                return;
            }

            this.visiblePasswords[subscriptionId] = !this.visiblePasswords[subscriptionId];
            this.copyCredential(`password-${subscriptionId}`, password);
        },

        debounceFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.pagination.current_page = 1;
                this.fetchData();
            }, 300);
        },
        get selectedSubscriptionCount() {
            if (this.selectionMode === 'all') {
                return Math.max(this.pagination.total - this.excludedIds.length, 0);
            }

            return this.selectedIds.length;
        },
        get hasSelectedSubscriptions() {
            return this.selectedSubscriptionCount > 0;
        },
        get allVisibleSubscriptionsSelected() {
            return this.subscriptions.length > 0 && this.subscriptions.every((subscription) => this.isSubscriptionSelected(subscription.id));
        },
        capitalize(str) {
            if (!str) return '';

            return str.charAt(0).toUpperCase() + str.slice(1);
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

        prevPage() {
            this.goToPage(this.pagination.current_page - 1);
        },

        nextPage() {
            this.goToPage(this.pagination.current_page + 1);
        },
        bulkDeleteConfirmationMessage() {
            if (this.selectionMode === 'all') {
                const selectedCount = Math.max(this.pagination.total - this.excludedIds.length, 0);

                return `Delete ${selectedCount} filtered subscription${selectedCount === 1 ? '' : 's'}? This will queue the cleanup and cannot be undone.`;
            }

            const count = this.selectedIds.length;
            return `Delete ${count} selected subscription${count === 1 ? '' : 's'}? This will queue the cleanup and cannot be undone.`;
        },
        async bulkDeleteSelected() {
            if (!this.hasSelectedSubscriptions) {
                return;
            }

            if (!window.confirm(this.bulkDeleteConfirmationMessage())) {
                return;
            }

            this.bulkDeleting = true;

            try {
                const payload = {
                    selection_mode: this.selectionMode,
                    ids: this.selectionMode === 'all' ? [] : this.selectedIds,
                    excluded_ids: this.selectionMode === 'all' ? this.excludedIds : [],
                    search: this.filters.search,
                    status: this.filters.status,
                };

                const response = await fetch('/subscriptions/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Bulk delete request failed');
                }

                this.clearSelection();
                await Promise.all([this.fetchData(), this.fetchStats()]);
                alert(data.message || 'Subscription bulk delete queued.');
            } catch (error) {
                console.error('Error queueing subscription bulk delete:', error);
                alert('Unable to queue subscription bulk delete. Please try again.');
            } finally {
                this.bulkDeleting = false;
            }
        },

        confirmDelete(subscription) {
            this.deleteModal.subscription = subscription;
            this.deleteModal.show = true;
        },

        async deleteSubscription() {
            if (!this.deleteModal.subscription) return;

            this.deleteModal.deleting = true;
            try {
                const response = await fetch(`/subscriptions/${this.deleteModal.subscription.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.deleteModal.show = false;
                    await Promise.all([this.fetchData(), this.fetchStats()]);
                } else {
                    alert('Error deleting subscription. Please try again.');
                }
            } finally {
                this.deleteModal.deleting = false;
            }
        },

        async suspendSubscription(subscription) {
            const response = await fetch(`/subscriptions/${subscription.id}/suspend`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                await Promise.all([this.fetchData(), this.fetchStats()]);
            } else {
                alert('Error suspending subscription. Please try again.');
            }
        }
    };
}
</script>
@endpush
@endsection
