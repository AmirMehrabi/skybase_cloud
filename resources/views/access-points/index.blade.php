@extends('layouts.admin')

@section('title', 'Access Point Management')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="accessPointsIndex()" x-cloak>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Access Point Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage wireless access points across your network</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="loadAccessPoints()" :disabled="loading" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                <svg class="w-4 h-4" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            <a href="{{ route('access-points.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Access Point
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Access Points -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Access Points</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.total"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Online APs -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Online</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.online"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Offline APs -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Offline</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.offline"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Connected Clients -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500">Total Connected Clients</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="stats.totalConnectedClients"></p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Filters</h3>
            <button x-show="hasActiveFilters()" @click="clearFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-medium" style="display: none;">
                Clear All
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <input type="text" x-model="filters.search" @input="debouncedLoad" placeholder="Search by name, MAC, SSID..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>

            <select x-model="filters.status" @change="loadAccessPoints(1)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Statuses</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="maintenance">Maintenance</option>
                <option value="decommissioned">Decommissioned</option>
            </select>

            <select x-model="filters.vendor" @change="loadAccessPoints(1)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Vendors</option>
                <option value="Ubiquiti">Ubiquiti</option>
                <option value="TP-Link">TP-Link</option>
                <option value="MikroTik">MikroTik</option>
                <option value="Cambium">Cambium</option>
                <option value="Ruckus">Ruckus</option>
            </select>

            <select x-model="filters.site" @change="loadAccessPoints(1)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Sites</option>
                <template x-for="option in filterOptions.sites" :key="option.value">
                    <option :value="option.value" x-text="option.label"></option>
                </template>
            </select>

            <select x-model="filters.frequency_band" @change="loadAccessPoints(1)" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Bands</option>
                <option value="2.4GHz">2.4 GHz</option>
                <option value="5GHz">5 GHz</option>
                <option value="6GHz">6 GHz</option>
                <option value="dual-band">Dual Band</option>
            </select>
        </div>
    </div>

    <!-- Access Points Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Access Point</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">MAC Address</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Site</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Band</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Clients</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="ap in accessPoints" :key="ap.id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900" x-text="ap.name"></span>
                                    <span class="text-xs text-gray-500" x-text="ap.ssid !== '—' ? 'SSID: ' + ap.ssid : ''"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="ap.model || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="ap.vendor"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 font-mono" x-text="ap.mac_address"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="ap.site"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="ap.frequency_band"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                      :class="{
                                          'bg-green-100 text-green-800 border-green-200': ap.status === 'online',
                                          'bg-red-100 text-red-800 border-red-200': ap.status === 'offline',
                                          'bg-yellow-100 text-yellow-800 border-yellow-200': ap.status === 'maintenance',
                                          'bg-gray-100 text-gray-800 border-gray-200': ap.status === 'decommissioned'
                                      }"
                                      x-text="ap.status.charAt(0).toUpperCase() + ap.status.slice(1)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700" x-text="ap.connected_clients + ' / ' + ap.max_clients"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.action-icon x-bind:href="urls.show + '/' + ap.id" icon="view" label="View" />
                                    <x-ui.action-icon x-bind:href="urls.edit + '/' + ap.id" icon="edit" label="Edit" />
                                    <x-ui.action-icon as="button" icon="delete" label="Delete" @click="confirmDelete(ap)" />
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="accessPoints.length === 0 && !loading" style="display: none;">
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-500">No access points found</p>
                                <button @click="clearFilters()" x-show="hasActiveFilters()" class="text-sm text-blue-600 hover:text-blue-700 font-medium" style="display: none;">
                                    Clear Filters
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="px-6 py-12 text-center" style="display: none;">
            <svg class="w-8 h-8 animate-spin text-blue-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <p class="text-sm text-gray-500 mt-2">Loading access points...</p>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > 0" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6" style="display: none;">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium" x-text="pagination.from"></span> to <span class="font-medium" x-text="pagination.to"></span> of <span class="font-medium" x-text="pagination.total"></span> access points
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="loadAccessPoints(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1 || loading"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Previous
                    </button>

                    <template x-for="(page, index) in paginationPages()" :key="`${page}-${index}`">
                        <button
                            type="button"
                            x-show="page !== '...'"
                            @click="loadAccessPoints(page)"
                            :disabled="loading"
                            :class="page === pagination.current_page ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                            class="inline-flex min-w-10 items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"
                            x-text="page"
                        ></button>
                        <span
                            x-show="page === '...'"
                            class="inline-flex min-w-10 items-center justify-center px-1 text-sm font-medium text-gray-500"
                            x-text="page"
                        ></span>
                    </template>

                    <button
                        type="button"
                        @click="loadAccessPoints(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page || loading"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <x-ui.delete-modal
        title="Delete Access Point"
        name="deleteModal.ap?.name"
        confirm-action="deleteAccessPoint()"
    />
</div>

@push('scripts')
<script>
function accessPointsIndex() {
    return {
        accessPoints: [],
        stats: {
            total: 0,
            online: 0,
            offline: 0,
            totalConnectedClients: 0
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0,
            from: 0,
            to: 0
        },
        filterOptions: {
            sites: []
        },
        filters: {
            search: '',
            status: '',
            vendor: '',
            site: '',
            frequency_band: ''
        },
        loading: true,
        deleteModal: {
            show: false,
            ap: null,
            deleting: false
        },
        debounceTimer: null,
        urls: {
            show: '{{ url('access-points') }}',
            edit: '{{ url('access-points') }}',
            destroy: '{{ url('access-points') }}'
        },

        init() {
            this.loadStats();
            this.loadFilterOptions();
            this.loadAccessPoints();
        },

        async loadAccessPoints(page = this.pagination.current_page || 1) {
            this.loading = true;
            try {
                this.pagination.current_page = page;

                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.vendor) params.append('vendor', this.filters.vendor);
                if (this.filters.site) params.append('site', this.filters.site);
                if (this.filters.frequency_band) params.append('frequency_band', this.filters.frequency_band);
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);

                const response = await fetch('{{ route('access-points.data') }}?' + params.toString());
                const data = await response.json();
                this.accessPoints = data.accessPoints;
                this.pagination = data.pagination;

                if (this.accessPoints.length === 0 && this.pagination.current_page > 1 && this.pagination.total > 0) {
                    await this.loadAccessPoints(this.pagination.current_page - 1);
                }
            } catch (error) {
                console.error('Error loading access points:', error);
                alert('Error loading access points. Please try again.');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const response = await fetch('{{ route('access-points.stats') }}');
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async loadFilterOptions() {
            try {
                const response = await fetch('{{ route('access-points.filter-options') }}');
                const data = await response.json();
                this.filterOptions = data;
            } catch (error) {
                console.error('Error loading filter options:', error);
            }
        },

        debouncedLoad() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.loadAccessPoints(1);
            }, 300);
        },

        hasActiveFilters() {
            return this.filters.search || this.filters.status || this.filters.vendor || this.filters.site || this.filters.frequency_band;
        },

        clearFilters() {
            this.filters = {
                search: '',
                status: '',
                vendor: '',
                site: '',
                frequency_band: ''
            };
            this.loadAccessPoints(1);
        },

        paginationPages() {
            if (this.pagination.last_page <= 7) {
                return Array.from({ length: this.pagination.last_page }, (_, index) => index + 1);
            }

            const pages = [1];
            const currentPage = this.pagination.current_page;
            const lastPage = this.pagination.last_page;
            let start = Math.max(2, currentPage - 1);
            let end = Math.min(lastPage - 1, currentPage + 1);

            if (currentPage <= 3) {
                start = 2;
                end = 4;
            } else if (currentPage >= lastPage - 2) {
                start = lastPage - 3;
                end = lastPage - 1;
            }

            if (start > 2) pages.push('...');
            for (let page = start; page <= end; page++) pages.push(page);
            if (end < lastPage - 1) pages.push('...');
            pages.push(lastPage);

            return pages;
        },

        confirmDelete(ap) {
            this.deleteModal.ap = ap;
            this.deleteModal.show = true;
        },

        async deleteAccessPoint() {
            if (!this.deleteModal.ap) return;

            this.deleteModal.deleting = true;
            try {
                const response = await fetch(`${this.urls.destroy}/${this.deleteModal.ap.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.deleteModal.show = false;
                    await this.loadAccessPoints(this.pagination.current_page);
                    this.loadStats();
                    alert('Access point deleted successfully.');
                } else {
                    alert('Error deleting access point. Please try again.');
                }
            } catch (error) {
                console.error('Error deleting access point:', error);
                alert('Error deleting access point. Please try again.');
            } finally {
                this.deleteModal.deleting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
