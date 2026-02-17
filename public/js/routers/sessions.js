// Router Sessions Alpine.js Component
function routerSessions() {
    const data = window.routerShowData || {};

    return {
        router: data.router || {},
        sessions: data.sessions || [],

        // Filters
        filters: {
            search: '',
            interface: '',
            minSpeed: '',
            sessionTime: ''
        },

        // Sorting
        sortColumn: null,
        sortAsc: true,

        // Pagination
        currentPage: 1,
        perPage: 15,

        // Get available interfaces for filter
        get availableInterfaces() {
            return [...new Set(this.sessions.map(s => s.interface))];
        },

        // Check if has active filters
        get hasActiveFilters() {
            return this.filters.search || this.filters.interface || this.filters.minSpeed || this.filters.sessionTime;
        },

        // Filtered and sorted sessions
        get filteredSessions() {
            let filtered = [...this.sessions];

            // Search filter
            if (this.filters.search) {
                const search = this.filters.search.toLowerCase();
                filtered = filtered.filter(s =>
                    s.username.toLowerCase().includes(search) ||
                    s.customer.toLowerCase().includes(search) ||
                    s.ip_address.includes(search) ||
                    s.mac_address.toLowerCase().includes(search)
                );
            }

            // Interface filter
            if (this.filters.interface) {
                filtered = filtered.filter(s => s.interface === this.filters.interface);
            }

            // Min speed filter
            if (this.filters.minSpeed) {
                const minSpeed = parseInt(this.filters.minSpeed);
                filtered = filtered.filter(s => {
                    const speed = parseInt(s.download_speed);
                    return speed >= minSpeed;
                });
            }

            // Session time filter
            if (this.filters.sessionTime) {
                filtered = filtered.filter(s => {
                    const time = this.parseSessionTime(s.session_time);
                    const maxTime = this.parseSessionTime(this.filters.sessionTime);
                    return time < maxTime;
                });
            }

            // Sorting
            if (this.sortColumn) {
                filtered.sort((a, b) => {
                    let valA = a[this.sortColumn];
                    let valB = b[this.sortColumn];

                    // Handle numeric values for speed
                    if (this.sortColumn === 'download_speed' || this.sortColumn === 'upload_speed') {
                        valA = parseInt(valA);
                        valB = parseInt(valB);
                    } else if (this.sortColumn === 'session_time') {
                        valA = this.parseSessionTime(valA);
                        valB = this.parseSessionTime(valB);
                    }

                    if (valA < valB) return this.sortAsc ? -1 : 1;
                    if (valA > valB) return this.sortAsc ? 1 : -1;
                    return 0;
                });
            }

            return filtered;
        },

        // Paginated sessions
        get paginatedSessions() {
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return this.filteredSessions.slice(start, end);
        },

        // Total pages
        get totalPages() {
            return Math.ceil(this.filteredSessions.length / this.perPage);
        },

        // Visible page numbers
        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start < maxVisible - 1) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            return pages;
        },

        // Parse session time string to minutes
        parseSessionTime(timeStr) {
            if (!timeStr) return 0;
            const match = timeStr.match(/(\d+)h\s*(\d+)?m?/);
            if (match) {
                const hours = parseInt(match[1]) || 0;
                const minutes = parseInt(match[2]) || 0;
                return hours * 60 + minutes;
            }
            const matchMin = timeStr.match(/(\d+)m/);
            if (matchMin) {
                return parseInt(matchMin[1]);
            }
            return 0;
        },

        // Get speed percentage for progress bar
        getSpeedPercentage(speedStr) {
            const speed = parseInt(speedStr);
            return Math.min(100, Math.round(speed / 2)); // Scale: 200Mbps = 100%
        },

        // Sort by column
        sortBy(column) {
            if (this.sortColumn === column) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortColumn = column;
                this.sortAsc = true;
            }
        },

        // Clear filters
        clearFilters() {
            this.filters = {
                search: '',
                interface: '',
                minSpeed: '',
                sessionTime: ''
            };
            this.currentPage = 1;
        },

        // Pagination
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

        goToPage(page) {
            this.currentPage = page;
        },

        // Refresh sessions
        refreshSessions() {
            alert('Refreshing sessions...');
            // In real app, this would call the API
        },

        // Disconnect session
        disconnectSession(session) {
            if (confirm(`Disconnect session for ${session.username}?`)) {
                const index = this.sessions.findIndex(s => s.id === session.id);
                if (index !== -1) {
                    this.sessions.splice(index, 1);
                }
            }
        },

        // Suspend session
        suspendSession(session) {
            if (confirm(`Suspend session for ${session.username}?`)) {
                alert(`Session ${session.username} suspended.`);
            }
        },

        // Limit speed
        limitSpeed(session) {
            const newSpeed = prompt(`Enter new download speed limit for ${session.username}:`, session.download_speed);
            if (newSpeed) {
                alert(`Speed limit updated to ${newSpeed}`);
            }
        },

        // View customer
        viewCustomer(session) {
            alert(`Opening customer details for ${session.customer}...`);
        },
    };
}
