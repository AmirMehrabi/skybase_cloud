@extends('layouts.admin')

@section('title', 'Organizations')

@section('content')
<div class="space-y-6" x-data="organizationsIndex()" x-init="init()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Organizations</h1>
            <p class="text-sm text-gray-500 mt-1">Group customers and manage shared billing defaults</p>
        </div>
        <a href="{{ route('organizations.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New Organization
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <template x-for="card in statCards" :key="card.label">
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-500" x-text="card.label"></p>
                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="card.value"></p>
            </div>
        </template>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <input type="text" x-model="search" placeholder="Search organizations..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <select x-model="status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select x-model="billing" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Billing States</option>
                <option value="enabled">Billing Enabled</option>
                <option value="disabled">Billing Disabled</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Organization</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customers</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Billing</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Default Service</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="organization in organizations" :key="organization.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <a :href="`/organizations/${organization.id}`" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="organization.name"></a>
                                    <span class="text-xs text-gray-500" x-text="organization.code"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="organization.customers_count"></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border" :class="organization.billing_enabled ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200'" x-text="organization.billing_enabled ? 'Enabled' : 'Disabled'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700" x-text="organization.default_plan"></div>
                                <div class="text-xs text-gray-500 capitalize" x-text="organization.default_billing_cycle"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border" :class="organization.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200'" x-text="organization.status"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.action-icon x-bind:href="`/organizations/${organization.id}`" icon="view" label="View" />
                                    <x-ui.action-icon x-bind:href="`/organizations/${organization.id}/edit`" icon="edit" label="Edit" />
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="organizations.length === 0" style="display: none;">
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No organizations found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="pagination.total > pagination.per_page" class="bg-white rounded-2xl border border-gray-200 shadow-sm px-6 py-4">
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

@push('scripts')
<script>
function organizationsIndex() {
    return {
        organizations: [],
        stats: { total: 0, active: 0, billing_enabled: 0, customers: 0 },
        search: '',
        status: '',
        billing: '',
        perPage: 100,
        currentPage: 1,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 100,
            total: 0,
            from: 0,
            to: 0,
        },
        debounceTimer: null,
        get statCards() {
            return [
                { label: 'Total Organizations', value: this.stats.total },
                { label: 'Active', value: this.stats.active },
                { label: 'Billing Enabled', value: this.stats.billing_enabled },
                { label: 'Assigned Customers', value: this.stats.customers },
            ];
        },
        init() {
            this.fetchStats();
            this.fetchOrganizations();
            this.$watch('search', () => this.debounceFetch());
            this.$watch('status', () => {
                this.currentPage = 1;
                this.fetchOrganizations();
            });
            this.$watch('billing', () => {
                this.currentPage = 1;
                this.fetchOrganizations();
            });
        },
        debounceFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.currentPage = 1;
                this.fetchOrganizations();
            }, 300);
        },
        async fetchStats() {
            const response = await fetch('/organizations/stats');
            this.stats = await response.json();
        },
        async fetchOrganizations() {
            const params = new URLSearchParams({
                search: this.search,
                status: this.status,
                billing: this.billing,
                page: this.currentPage,
                per_page: this.perPage,
            });
            const response = await fetch(`/organizations/data?${params}`);
            const data = await response.json();
            this.organizations = data.organizations;
            this.pagination = data.pagination;
            this.currentPage = data.pagination.current_page;
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
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page || page === this.pagination.current_page) {
                return;
            }

            this.currentPage = page;
            this.fetchOrganizations();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        prevPage() {
            this.goToPage(this.pagination.current_page - 1);
        },
        nextPage() {
            this.goToPage(this.pagination.current_page + 1);
        },
    };
}
</script>
@endpush
@endsection
