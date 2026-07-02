@props([
    'endpoint',
    'title' => 'Bandwidth usage',
    'description' => 'Download and upload throughput over time.',
])

<section
    class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm sm:p-6"
    x-data="bandwidthChart(@js($endpoint))"
    x-init="load()"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-950">{{ $title }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        </div>
        <div class="flex flex-wrap gap-1 rounded-xl bg-[#f6f1e8] p-1" aria-label="Usage chart range">
            <template x-for="option in ranges" :key="option.value">
                <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    :class="range === option.value ? 'bg-white text-[#0d2f35] shadow-sm' : 'text-slate-500 hover:text-slate-950'"
                    @click="changeRange(option.value)"
                    x-text="option.label"
                ></button>
            </template>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs font-medium text-slate-600">
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Download</span>
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Upload</span>
    </div>

    <div class="relative mt-4 h-72 overflow-hidden rounded-xl border border-slate-900/10 bg-[#fbf7ed]">
        <div x-show="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/75 text-sm font-medium text-slate-500">
            Loading usage…
        </div>

        <svg x-show="points.length" class="h-full w-full" viewBox="0 0 800 280" preserveAspectRatio="none" role="img" aria-label="Bandwidth usage line chart">
            <g stroke="rgba(15,23,42,0.08)" stroke-width="1">
                <line x1="45" y1="30" x2="780" y2="30"></line>
                <line x1="45" y1="90" x2="780" y2="90"></line>
                <line x1="45" y1="150" x2="780" y2="150"></line>
                <line x1="45" y1="210" x2="780" y2="210"></line>
                <line x1="45" y1="250" x2="780" y2="250"></line>
            </g>
            <path :d="pathFor('rx_bps')" fill="none" stroke="#10b981" stroke-width="3" vector-effect="non-scaling-stroke"></path>
            <path :d="pathFor('tx_bps')" fill="none" stroke="#f59e0b" stroke-width="3" vector-effect="non-scaling-stroke"></path>
        </svg>

        <div x-show="!loading && !hasData" class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
            <svg class="h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3v18h18M7 16l4-4 3 3 5-7"></path>
            </svg>
            <p class="mt-3 text-sm font-semibold text-slate-700">Usage data is not available yet</p>
            <p class="mt-1 text-xs text-slate-500">The graph will populate when monitoring samples are collected.</p>
        </div>
    </div>

    <div x-show="points.length" class="mt-3 flex items-center justify-between text-xs text-slate-500">
        <span x-text="points[0]?.time"></span>
        <span>Peak <strong class="font-semibold text-slate-700" x-text="formatRate(maxValue)"></strong></span>
        <span x-text="points[points.length - 1]?.time"></span>
    </div>
</section>

@once
    @push('scripts')
        <script>
            window.bandwidthChart = (endpoint) => ({
                endpoint,
                range: '24h',
                ranges: [
                    { value: '1h', label: '1H' },
                    { value: '6h', label: '6H' },
                    { value: '24h', label: '24H' },
                    { value: '7d', label: '7D' },
                    { value: '30d', label: '30D' },
                ],
                points: [],
                hasData: false,
                loading: true,
                get maxValue() {
                    return Math.max(1, ...this.points.flatMap((point) => [
                        Number(point.rx_bps) || 0,
                        Number(point.tx_bps) || 0,
                    ]));
                },
                async load() {
                    this.loading = true;

                    try {
                        const url = new URL(this.endpoint, window.location.origin);
                        url.searchParams.set('range', this.range);
                        const response = await fetch(url, {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const payload = response.ok ? await response.json() : {};
                        this.points = Array.isArray(payload.chartData) ? payload.chartData : [];
                        this.hasData = Boolean(payload.hasData);
                    } catch (error) {
                        this.points = [];
                        this.hasData = false;
                    } finally {
                        this.loading = false;
                    }
                },
                changeRange(range) {
                    if (this.range === range) {
                        return;
                    }

                    this.range = range;
                    this.load();
                },
                pathFor(field) {
                    const width = 735;
                    const height = 220;
                    const left = 45;
                    const top = 30;
                    let path = '';
                    let drawing = false;

                    this.points.forEach((point, index) => {
                        const value = point[field];

                        if (value === null || value === undefined) {
                            drawing = false;
                            return;
                        }

                        const x = left + (this.points.length === 1 ? 0 : (index / (this.points.length - 1)) * width);
                        const y = top + height - ((Number(value) || 0) / this.maxValue) * height;
                        path += `${drawing ? ' L' : ' M'} ${x.toFixed(2)} ${y.toFixed(2)}`;
                        drawing = true;
                    });

                    return path;
                },
                formatRate(value) {
                    const rate = Number(value) || 0;

                    if (rate >= 1000000000) {
                        return `${(rate / 1000000000).toFixed(2)} Gbps`;
                    }

                    if (rate >= 1000000) {
                        return `${(rate / 1000000).toFixed(2)} Mbps`;
                    }

                    if (rate >= 1000) {
                        return `${(rate / 1000).toFixed(2)} Kbps`;
                    }

                    return `${rate.toFixed(0)} bps`;
                },
            });
        </script>
    @endpush
@endonce
