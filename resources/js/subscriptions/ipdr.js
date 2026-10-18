export function createSubscriptionIpdr({ url }) {
    return {
        url,
        range: '1h',
        sourceIp: '',
        destinationIp: '',
        customFrom: '',
        customTo: '',
        loading: false,
        loaded: false,
        error: '',
        report: null,
        hover: null,
        chartWidth: 1000,
        requestNumber: 0,
        controller: null,

        init() {
            const query = new URLSearchParams(window.location.search);
            this.sourceIp = query.get('source_ip') || '';
            this.destinationIp = query.get('destination_ip') || '';
            if (query.has('from') && query.has('to')) {
                this.range = 'custom';
                this.customFrom = this.localInput(query.get('from'));
                this.customTo = this.localInput(query.get('to'));
            }
            this.$watch('tab', (value) => {
                if (value === 'ipdr' && !this.loaded && !this.loading) this.load();
            });
            if (this.tab === 'ipdr') this.load();
        },

        localInput(value) {
            const date = new Date(value);
            if (!Number.isFinite(date.getTime())) return '';
            return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        },

        async load() {
            this.controller?.abort();
            const request = ++this.requestNumber;
            this.controller = new AbortController();
            this.loading = true;
            this.error = '';
            this.report = null;
            this.hover = null;
            try {
                const to = this.range === 'custom' ? new Date(this.customTo) : new Date();
                const hours = { '1h': 1, '24h': 24, '7d': 168 }[this.range];
                const from = this.range === 'custom' ? new Date(this.customFrom) : new Date(to.getTime() - hours * 3600000);
                if (!Number.isFinite(from.getTime()) || !Number.isFinite(to.getTime()) || from >= to) {
                    throw new Error('Choose a valid start and end time.');
                }
                const query = new URLSearchParams({ analytics: '1', from: from.toISOString(), to: to.toISOString() });
                if (this.sourceIp.trim()) query.set('source_ip', this.sourceIp.trim());
                if (this.destinationIp.trim()) query.set('destination_ip', this.destinationIp.trim());
                const response = await fetch(`${this.url}?${query}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: this.controller.signal,
                });
                const body = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(response.status === 422 ? (body.message || 'Check the selected time range and IP addresses.') : response.status === 401 ? 'Your session has expired. Sign in again to view traffic.' : (body.message || 'IPDR is temporarily unavailable. Please try again.'));
                }
                if (!body.analytics || !Array.isArray(body.analytics.time_series)) {
                    throw new Error('Detailed traffic analytics are not available yet. Please try again shortly.');
                }
                if (request === this.requestNumber) this.report = body;
            } catch (error) {
                if (request === this.requestNumber && error.name !== 'AbortError') this.error = error.message;
            } finally {
                if (request === this.requestNumber) {
                    this.loading = false;
                    this.loaded = true;
                }
            }
        },

        get points() { return this.report?.analytics?.time_series || []; },
        get maximum() {
            return Math.max(1, ...this.points.flatMap((point) => [Number(point.upload_bytes), Number(point.download_bytes)]));
        },
        x(index) { return 62 + (index + 0.5) * (this.chartWidth - 104) / Math.max(1, this.points.length); },
        y(value) { return 236 - Math.max(0, Number(value)) / this.maximum * 210; },
        line(field) {
            return this.points.map((point, index) => `${index ? 'L' : 'M'} ${this.x(index)} ${this.y(point[field])}`).join(' ');
        },
        area(field) {
            if (!this.points.length) return '';
            return `${this.line(field)} L ${this.x(this.points.length - 1)} 236 L ${this.x(0)} 236 Z`;
        },
        move(event) {
            if (!this.points.length) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const coordinate = (event.clientX - rect.left) / rect.width * this.chartWidth;
            const index = Math.min(this.points.length - 1, Math.max(0, Math.floor((coordinate - 62) / (this.chartWidth - 104) * this.points.length)));
            this.hover = { index, point: this.points[index] };
        },
        formatBytes(value = 0) {
            const number = Math.max(0, Number(value) || 0);
            const units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
            const index = Math.min(units.length - 1, number > 0 ? Math.floor(Math.log(number) / Math.log(1024)) : 0);
            return `${(number / 1024 ** index).toLocaleString(undefined, { maximumFractionDigits: index ? 2 : 0 })} ${units[index]}`;
        },
        formatTime(value) {
            return value ? new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
        },
        share(row, rows) {
            const total = rows.reduce((sum, item) => sum + Number(item.bytes), 0);
            return total ? Number(row.bytes) / total * 100 : 0;
        },
        barWidth(row, rows) {
            return `${Math.max(0, Number(row.bytes)) / Math.max(1, ...rows.map((item) => Number(item.bytes))) * 100}%`;
        },
        filterIp(direction, ip) {
            if (direction === 'source') this.sourceIp = ip;
            else this.destinationIp = ip;
            this.load();
        },
        resetFilters() {
            this.sourceIp = '';
            this.destinationIp = '';
            this.load();
        },
    };
}
