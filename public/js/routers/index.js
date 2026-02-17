// Router Index Alpine.js Component
function routersIndex() {
    return {
        routers: window.routersData || [],

        // Filters
        search: '',
        status: '',
        vendor: '',
        site: '',

        // Stats
        get stats() {
            return {
                total: this.routers.length,
                online: this.routers.filter(r => r.status === 'online').length,
                offline: this.routers.filter(r => r.status === 'offline').length,
                activeSessions: this.routers.reduce((sum, r) => sum + (r.active_sessions_count || 0), 0)
            };
        },

        // Filter options
        get filterOptions() {
            const sites = [...new Set(this.routers.map(r => r.site).filter(Boolean))];
            return {
                sites: sites.map(s => ({ value: s, label: s }))
            };
        },

        // Check if has active filters
        get hasActiveFilters() {
            return this.search || this.status || this.vendor || this.site;
        },

        // Filtered routers
        get filteredRouters() {
            return this.routers.filter(router => {
                // Search filter
                if (this.search) {
                    const searchLower = this.search.toLowerCase();
                    const matchesSearch =
                        router.name.toLowerCase().includes(searchLower) ||
                        router.ip_address.includes(this.search) ||
                        (router.site && router.site.toLowerCase().includes(searchLower)) ||
                        (router.location && router.location.toLowerCase().includes(searchLower));
                    if (!matchesSearch) return false;
                }

                // Status filter
                if (this.status && router.status !== this.status) return false;

                // Vendor filter
                if (this.vendor && router.vendor !== this.vendor) return false;

                // Site filter
                if (this.site && router.site !== this.site) return false;

                return true;
            });
        },

        // Clear all filters
        clearFilters() {
            this.search = '';
            this.status = '';
            this.vendor = '';
            this.site = '';
        },

        // Refresh router status (simulated)
        refreshStatus() {
            // Simulate API call
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refreshing...
            `;

            setTimeout(() => {
                // Randomly update some router data
                this.routers.forEach(router => {
                    if (router.status === 'online') {
                        router.cpu_usage = Math.min(100, Math.max(5, router.cpu_usage + Math.floor(Math.random() * 21 - 10)));
                        router.memory_usage = Math.min(100, Math.max(20, router.memory_usage + Math.floor(Math.random() * 11 - 5)));
                        router.active_sessions_count = Math.max(0, router.active_sessions_count + Math.floor(Math.random() * 11 - 5));
                    }
                });

                btn.disabled = false;
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Status
                `;
            }, 1500);
        },

        // Toggle router status
        toggleRouterStatus(router) {
            if (router.status === 'online') {
                if (confirm(`Are you sure you want to disable "${router.name}"? This will disconnect all active sessions.`)) {
                    router.status = 'offline';
                    router.uptime = null;
                    router.cpu_usage = 0;
                    router.memory_usage = 0;
                    router.active_sessions_count = 0;
                }
            } else {
                // Simulate reconnecting
                router.status = 'online';
                router.uptime = '0d 0h';
                router.cpu_usage = Math.floor(Math.random() * 30 + 10);
                router.memory_usage = Math.floor(Math.random() * 40 + 30);
                router.active_sessions_count = Math.floor(Math.random() * 50 + 10);
            }
        }
    };
}
