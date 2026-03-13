document.addEventListener('alpine:init', () => {
    Alpine.data('customersIndex', () => ({
        customers: [],
        stats: { total: 0, active: 0, suspended: 0, overdue: 0 },
        filterOptions: { statuses: [], plans: [], sites: [], routers: [] },

        // Filters
        search: '',
        status: '',
        plan: '',
        site: '',
        router: '',

        // Pagination
        perPage: 15,
        currentPage: 1,

        // Loading state
        loading: false,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0,
        },

        // Initialize
        async init() {
            await Promise.all([
                this.fetchFilterOptions(),
                this.fetchStats(),
                this.fetchCustomers(),
            ]);

            // Watch for filter changes
            this.$watch('search', () => this.debounceFetch());
            this.$watch('status', () => this.fetchCustomers());
            this.$watch('plan', () => this.fetchCustomers());
            this.$watch('site', () => this.fetchCustomers());
            this.$watch('router', () => this.fetchCustomers());
        },

        // API calls
        async fetchCustomers() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    per_page: this.perPage,
                    page: this.currentPage,
                    search: this.search,
                    status: this.status,
                    plan: this.plan,
                    site: this.site,
                    router: this.router,
                });

                const response = await fetch(`/customers/data?${params}`);
                const data = await response.json();

                this.customers = data.customers;
                this.pagination = data.pagination;
                this.currentPage = data.pagination.current_page;
            } catch (error) {
                console.error('Error fetching customers:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchFilterOptions() {
            try {
                const response = await fetch('/customers/filter-options');
                this.filterOptions = await response.json();
            } catch (error) {
                console.error('Error fetching filter options:', error);
            }
        },

        async fetchStats() {
            try {
                const response = await fetch('/customers/stats');
                this.stats = await response.json();
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        },

        // Debounce for search input
        debounceTimer: null,
        debounceFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.currentPage = 1;
                this.fetchCustomers();
            }, 300);
        },

        // Computed
        get paginatedCustomers() {
            return this.customers;
        },

        get totalPages() {
            return this.pagination.last_page;
        },

        get totalCustomers() {
            return this.pagination.total;
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
            this.fetchCustomers();
        },

        goToPage(page) {
            this.currentPage = page;
            this.fetchCustomers();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.fetchCustomers();
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.fetchCustomers();
            }
        },

        // Helper methods
        formatBalance(amount) {
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(amount);
            return formatted;
        },

        getStatusBadgeClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800 border-green-200',
                pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                suspended: 'bg-red-100 text-red-800 border-red-200',
                inactive: 'bg-gray-100 text-gray-800 border-gray-200',
            };
            return classes[status] || classes.inactive;
        },
    }));
});
