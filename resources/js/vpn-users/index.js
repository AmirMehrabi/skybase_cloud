document.addEventListener('alpine:init', () => {
    Alpine.data('vpnUsersIndex', () => ({
        vpnUsers: [],
        stats: { total: 0, active: 0, inactive: 0, online: 0, offline: 0, recentLogins: 0 },

        search: '',
        active: '',
        online: '',

        perPage: 15,
        currentPage: 1,
        loading: false,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0,
        },

        async init() {
            await Promise.all([
                this.fetchStats(),
                this.fetchVpnUsers(),
            ]);

            this.$watch('search', () => this.debounceFetch());
            this.$watch('active', () => {
                this.currentPage = 1;
                this.fetchVpnUsers();
            });
            this.$watch('online', () => {
                this.currentPage = 1;
                this.fetchVpnUsers();
            });
        },

        async fetchVpnUsers() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    per_page: this.perPage,
                    page: this.currentPage,
                    search: this.search,
                    active: this.active,
                    online: this.online,
                });

                const response = await fetch(`/vpn-users/data?${params}`);
                const data = await response.json();

                this.vpnUsers = data.vpnUsers;
                this.pagination = data.pagination;
                this.currentPage = data.pagination.current_page;
            } catch (error) {
                console.error('Error fetching VPN users:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchStats() {
            try {
                const response = await fetch('/vpn-users/stats');
                this.stats = await response.json();
            } catch (error) {
                console.error('Error fetching VPN user stats:', error);
            }
        },

        debounceTimer: null,
        debounceFetch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.currentPage = 1;
                this.fetchVpnUsers();
            }, 300);
        },

        get totalPages() {
            return this.pagination.last_page;
        },

        get totalVpnUsers() {
            return this.pagination.total;
        },

        get hasActiveFilters() {
            return this.search || this.active || this.online;
        },

        clearFilters() {
            this.search = '';
            this.active = '';
            this.online = '';
            this.currentPage = 1;
            this.fetchVpnUsers();
        },

        goToPage(page) {
            this.currentPage = page;
            this.fetchVpnUsers();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.fetchVpnUsers();
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.fetchVpnUsers();
            }
        },

        formatBytes(bytes) {
            if (!bytes) {
                return '0 B';
            }

            const base = 1024;
            const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            const unitIndex = Math.floor(Math.log(bytes) / Math.log(base));

            return `${parseFloat((bytes / Math.pow(base, unitIndex)).toFixed(1))} ${units[unitIndex]}`;
        },

        async copyConfig(config) {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(config);

                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = config;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        },

        downloadConfig(config) {
            const blob = new Blob([config], { type: 'application/x-openvpn-profile' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = 'client.ovpn';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },
    }));
});
