document.addEventListener('alpine:init', () => {
    Alpine.data('customersIndex', () => ({
        customers: [],
        stats: { total: 0, active: 0, suspended: 0, overdue: 0 },
        filterOptions: { statuses: [], plans: [], sites: [], routers: [], organizations: [] },

        // Filters
        search: '',
        status: '',
        plan: '',
        site: '',
        router: '',
        organization: '',

        // Pagination
        perPage: 100,
        currentPage: 1,

        // Loading state
        loading: false,
        bulkDeleting: false,
        selectedIds: [],
        excludedIds: [],
        selectionMode: 'selected',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 100,
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
            this.$watch('search', () => {
                this.clearSelection();
                this.debounceFetch();
            });
            this.$watch('status', () => this.refreshFromFilters());
            this.$watch('plan', () => this.refreshFromFilters());
            this.$watch('site', () => this.refreshFromFilters());
            this.$watch('router', () => this.refreshFromFilters());
            this.$watch('organization', () => this.refreshFromFilters());
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
                    organization: this.organization,
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

        refreshFromFilters() {
            this.clearSelection();
            this.currentPage = 1;
            this.fetchCustomers();
        },

        // Computed
        get paginatedCustomers() {
            return this.customers;
        },

        get totalPages() {
            return this.pagination.last_page;
        },

        get paginationPages() {
            const totalPages = this.totalPages;
            const currentPage = this.currentPage;

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

        get totalCustomers() {
            return this.pagination.total;
        },

        get hasActiveFilters() {
            return this.search || this.status || this.plan || this.site || this.router || this.organization;
        },

        get selectedCustomerCount() {
            if (this.selectionMode === 'all') {
                return Math.max(this.pagination.total - this.excludedIds.length, 0);
            }

            return this.selectedIds.length;
        },

        get hasSelectedCustomers() {
            return this.selectedCustomerCount > 0;
        },

        get allVisibleCustomersSelected() {
            return this.customers.length > 0 && this.customers.every((customer) => this.isCustomerSelected(customer.id));
        },

        get someVisibleCustomersSelected() {
            return this.customers.some((customer) => this.isCustomerSelected(customer.id));
        },

        // Actions
        clearFilters() {
            this.search = '';
            this.status = '';
            this.plan = '';
            this.site = '';
            this.router = '';
            this.organization = '';
            this.currentPage = 1;
            this.clearSelection();
            this.fetchCustomers();
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

        isCustomerSelected(customerId) {
            if (this.selectionMode === 'all') {
                return !this.excludedIds.includes(customerId);
            }

            return this.selectedIds.includes(customerId);
        },

        toggleVisibleSelection(checked) {
            const visibleIds = this.customers.map((customer) => customer.id);

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

        toggleCustomerSelection(customerId, checked) {
            if (this.selectionMode === 'all') {
                if (checked) {
                    this.excludedIds = this.excludedIds.filter((id) => id !== customerId);
                } else if (!this.excludedIds.includes(customerId)) {
                    this.excludedIds.push(customerId);
                }

                return;
            }

            if (checked) {
                if (!this.selectedIds.includes(customerId)) {
                    this.selectedIds.push(customerId);
                }
            } else {
                this.selectedIds = this.selectedIds.filter((id) => id !== customerId);
            }
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

        bulkDeleteConfirmationMessage() {
            if (this.selectionMode === 'all') {
                const excludedCount = this.excludedIds.length;
                const totalCount = this.pagination.total;
                const selectedCount = Math.max(totalCount - excludedCount, 0);

                return `Delete ${selectedCount} filtered customer${selectedCount === 1 ? '' : 's'}? This will queue the cleanup and cannot be undone.`;
            }

            const count = this.selectedIds.length;
            return `Delete ${count} selected customer${count === 1 ? '' : 's'}? This will queue the cleanup and cannot be undone.`;
        },

        async bulkDeleteSelected() {
            if (!this.hasSelectedCustomers) {
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
                    search: this.search,
                    status: this.status,
                    plan: this.plan,
                    site: this.site,
                    router: this.router,
                    organization: this.organization,
                };

                const response = await fetch('/customers/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Bulk delete request failed');
                }

                this.clearSelection();
                await Promise.all([this.fetchCustomers(), this.fetchStats()]);

                window.alert(data.message || 'Customer bulk delete queued.');
            } catch (error) {
                console.error('Error queueing customer bulk delete:', error);
                window.alert('Unable to queue customer bulk delete. Please try again.');
            } finally {
                this.bulkDeleting = false;
            }
        },

        async deleteCustomer(customer) {
            if (!customer || !customer.id) {
                return;
            }

            const confirmed = window.confirm(`Delete ${customer.name} and all related data? This action cannot be undone.`);
            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch(`/customers/${customer.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector("meta[name=\"csrf-token\"]")?.content ?? "",
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error("Delete request failed");
                }

                await Promise.all([this.fetchCustomers(), this.fetchStats()]);
            } catch (error) {
                console.error("Error deleting customer:", error);
                window.alert("Unable to delete customer. Please try again.");
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

        formatValues(values) {
            if (!Array.isArray(values) || values.length === 0) {
                return 'N/A';
            }

            return values.join(', ');
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
