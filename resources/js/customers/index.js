import { customers, stats, filterOptions } from './data.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('customersIndex', () => ({
        customers: customers,
        stats: stats,
        filterOptions: filterOptions,

        // Filters
        search: '',
        status: '',
        plan: '',
        site: '',
        router: '',

        // Pagination
        perPage: 10,
        currentPage: 1,

        // Computed
        get filteredCustomers() {
            let filtered = [...this.customers];

            // Search filter
            if (this.search) {
                const search = this.search.toLowerCase();
                filtered = filtered.filter(c =>
                    c.name.toLowerCase().includes(search) ||
                    c.customer_code.toLowerCase().includes(search) ||
                    c.email.toLowerCase().includes(search) ||
                    c.phone.includes(search)
                );
            }

            // Status filter
            if (this.status) {
                filtered = filtered.filter(c => c.status === this.status);
            }

            // Plan filter
            if (this.plan) {
                filtered = filtered.filter(c => c.plan === this.plan);
            }

            // Site filter
            if (this.site) {
                filtered = filtered.filter(c => c.site === this.site);
            }

            // Router filter
            if (this.router) {
                filtered = filtered.filter(c => c.router === this.router);
            }

            return filtered;
        },

        get paginatedCustomers() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.filteredCustomers.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.filteredCustomers.length / this.perPage);
        },

        get totalCustomers() {
            return this.filteredCustomers.length;
        },

        get hasActiveFilters() {
            return this.search || this.status || this.plan || this.site || this.router;
        },

        // Actions
        clearFilters() {
            this.search = '';
            this.status = '';
            this.plan = '';
            this.site = '';
            this.router = '';
            this.currentPage = 1;
        },

        goToPage(page) {
            this.currentPage = page;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },

        // Helper methods
        formatBalance(amount) {
            return '$' + amount.toFixed(2);
        },

        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800 border-green-200',
                pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                suspended: 'bg-red-100 text-red-800 border-red-200',
                terminated: 'bg-gray-100 text-gray-800 border-gray-200',
            };
            return classes[status] || classes.terminated;
        },
    }));
});
