<div id="subscription-ipdr-panel" x-show="tab === 'ipdr'" x-cloak
    x-data="subscriptionIpdr({ url: @js(route('subscriptions.ipdr', $ipdrSubscription)) })"
    :aria-busy="loading" class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">IPDR traffic analysis</h3>
            <p class="mt-1 text-sm text-gray-500">Explore this subscription’s download sources, upload destinations, and traffic over time.</p>
        </div>
        <button type="button" @click="load()" :disabled="loading" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
            <i class="fas fa-rotate-right" aria-hidden="true"></i> Refresh
        </button>
    </div>
    <form @submit.prevent="load()" class="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-input.select id="ipdr-range" name="ipdr_range" label="Time range" x-model="range" :options="['1h' => 'Last hour', '24h' => 'Last 24 hours', '7d' => 'Last 7 days', 'custom' => 'Custom range']" />
            <x-input.text id="ipdr-source" name="ipdr_source_ip" label="Source IP" placeholder="All source addresses" x-model="sourceIp" />
            <x-input.text id="ipdr-destination" name="ipdr_destination_ip" label="Destination IP" placeholder="All destination addresses" x-model="destinationIp" />
        </div>
        <div x-show="range === 'custom'" x-cloak class="grid grid-cols-1 gap-x-4 sm:grid-cols-2">
            <x-input.text id="ipdr-from" name="ipdr_from" type="datetime-local" label="From (local time)" x-model="customFrom" />
            <x-input.text id="ipdr-to" name="ipdr_to" type="datetime-local" label="To (local time)" x-model="customTo" />
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" :disabled="loading" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">Apply filters</button>
            <button type="button" @click="resetFilters()" x-show="sourceIp || destinationIp" class="text-sm font-medium text-gray-600 hover:text-gray-900">Clear IP filters</button>
            <p class="text-xs text-gray-500">Times are displayed in your local timezone. RADIUS remains the quota source.</p>
        </div>
    </form>
    <div x-show="error" x-cloak role="alert" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <span x-text="error"></span><button type="button" @click="load()" class="font-semibold underline">Try again</button>
    </div>
    <div x-show="loading" role="status" class="rounded-xl border border-gray-200 bg-white p-12 text-center text-sm text-gray-500">
        <i class="fas fa-circle-notch fa-spin mr-2" aria-hidden="true"></i> Loading subscription traffic…
    </div>
    <template x-if="report && !loading">
        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5"><p class="text-sm font-medium text-blue-700">Download</p><p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatBytes(report.usage.download_bytes)"></p><p class="mt-1 text-xs text-gray-500">Internet traffic received</p></div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-5"><p class="text-sm font-medium text-emerald-700">Upload</p><p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatBytes(report.usage.upload_bytes)"></p><p class="mt-1 text-xs text-gray-500">Internet traffic sent</p></div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5"><p class="text-sm font-medium text-gray-600">Internal traffic</p><p class="mt-2 text-2xl font-bold text-gray-900" x-text="formatBytes(report.usage.internal_bytes)"></p><p class="mt-1 text-xs text-gray-500">Between managed subscriptions</p></div>
                <div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-sm font-medium text-gray-600">Distinct remote IPs</p><p class="mt-2 text-2xl font-bold text-gray-900"><span x-text="report.analytics.source_ip_count"></span> / <span x-text="report.analytics.destination_ip_count"></span></p><p class="mt-1 text-xs text-gray-500">Download sources / upload destinations</p></div>
            </div>
            <div class="rounded-xl border border-gray-200 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><h4 class="font-semibold text-gray-900">Traffic over time</h4><p class="mt-1 text-xs text-gray-500" x-text="`${formatTime(report.from)} – ${formatTime(report.to)} · ${report.analytics.bucket_seconds / 60} min intervals`"></p></div>
                    <div class="flex gap-4 text-xs text-gray-600"><span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>Download</span><span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span>Upload</span></div>
                </div>
                <div class="relative mt-5">
                    <svg :viewBox="`0 0 ${chartWidth} 280`" x-effect="if (tab === 'ipdr') $nextTick(() => chartWidth = Math.max(320, $el.clientWidth))" @resize.window="chartWidth = Math.max(320, $el.clientWidth)" class="h-64 w-full sm:h-80" role="img" aria-label="Subscription download and upload bytes over time" @mousemove="move($event)" @mouseleave="hover = null">
                        <defs><linearGradient id="ipdr-download-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#2563eb" stop-opacity="0.18"/><stop offset="100%" stop-color="#2563eb" stop-opacity="0.01"/></linearGradient><linearGradient id="ipdr-upload-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#059669" stop-opacity="0.14"/><stop offset="100%" stop-color="#059669" stop-opacity="0.01"/></linearGradient></defs>
                        @foreach([0, 0.25, 0.5, 0.75, 1] as $fraction)
                        <g><line x1="62" :x2="chartWidth - 42" :y1="y(maximum * {{ $fraction }})" :y2="y(maximum * {{ $fraction }})" stroke="#e5e7eb" stroke-dasharray="4 4"/><text x="52" :y="y(maximum * {{ $fraction }}) + 4" text-anchor="end" fill="#9ca3af" font-size="11" x-text="formatBytes(maximum * {{ $fraction }})"></text></g>
                        @endforeach
                        <path :d="area('download_bytes')" fill="url(#ipdr-download-fill)"></path><path :d="area('upload_bytes')" fill="url(#ipdr-upload-fill)"></path>
                        <path :d="line('download_bytes')" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path><path :d="line('upload_bytes')" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <line x-show="hover" :x1="x(hover?.index ?? 0)" :x2="x(hover?.index ?? 0)" y1="26" y2="236" stroke="#64748b" stroke-dasharray="4 4"/>
                        <text x="62" y="264" fill="#9ca3af" font-size="11" x-text="formatTime(report.from)"></text><text :x="chartWidth / 2" y="264" class="hidden sm:block" text-anchor="middle" fill="#9ca3af" font-size="11" x-text="formatTime(points[Math.floor(points.length / 2)]?.at)"></text><text :x="chartWidth - 42" y="264" text-anchor="end" fill="#9ca3af" font-size="11" x-text="formatTime(report.to)"></text>
                    </svg>
                    <div x-show="hover" x-cloak class="pointer-events-none absolute right-4 top-2 rounded-lg bg-gray-900 px-3 py-2 text-xs text-white shadow-lg"><p x-text="formatTime(hover?.point.at)"></p><p class="mt-1 text-blue-200" x-text="'Download: ' + formatBytes(hover?.point.download_bytes)"></p><p class="text-emerald-200" x-text="'Upload: ' + formatBytes(hover?.point.upload_bytes)"></p></div>
                    <div x-show="!report.usage.download_bytes && !report.usage.upload_bytes" class="pointer-events-none absolute inset-0 flex items-center justify-center"><span class="rounded-full border border-gray-200 bg-white px-4 py-2 text-sm text-gray-500">No Internet traffic in this period</span></div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Bytes per interval. Flow counters are distributed across time; interval values are estimates.</p>
            </div>
            <div x-show="report.analytics.missing_detail_bytes > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Usage totals include <strong x-text="formatBytes(report.analytics.missing_detail_bytes)"></strong> whose flow details have expired. IP rankings and recent observations cover retained details only.</div>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                @foreach(['source' => ['title' => 'Top source IPs', 'hint' => 'Where downloads came from', 'key' => 'top_source_ips', 'color' => 'bg-blue-600'], 'destination' => ['title' => 'Top destination IPs', 'hint' => 'Where uploads were sent', 'key' => 'top_destination_ips', 'color' => 'bg-emerald-600']] as $direction => $chart)
                <div class="min-w-0 rounded-xl border border-gray-200 p-5">
                    <h4 class="font-semibold text-gray-900">{{ $chart['title'] }}</h4><p class="mt-1 text-xs text-gray-500">{{ $chart['hint'] }} · Top 20 · Click an IP to filter</p>
                    <div class="mt-5 max-h-96 space-y-4 overflow-y-auto pr-2">
                        <template x-for="row in report.analytics.{{ $chart['key'] }}" :key="row.ip">
                            <button type="button" @click="filterIp('{{ $direction }}', row.ip)" class="block w-full rounded-lg p-1 text-left hover:bg-gray-50">
                                <div class="flex items-center justify-between gap-3 text-xs"><span class="truncate font-mono font-medium text-gray-800" x-text="row.ip"></span><span class="shrink-0 font-semibold text-gray-700" x-text="formatBytes(row.bytes)"></span></div>
                                <div class="mt-2 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full {{ $chart['color'] }}" :style="{ width: barWidth(row, report.analytics.{{ $chart['key'] }}) }"></div></div>
                                <p class="mt-1 text-xs text-gray-400"><span x-text="row.flows.toLocaleString()"></span> observations · <span x-text="row.packets.toLocaleString()"></span> packets</p>
                            </button>
                        </template>
                        <p x-show="!report.analytics.{{ $chart['key'] }}.length" class="py-8 text-center text-sm text-gray-500">No {{ $direction }} IP traffic in this period.</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="overflow-hidden rounded-xl border border-gray-200">
                <div class="border-b border-gray-200 p-5"><h4 class="font-semibold text-gray-900">Recent flow observations</h4><p class="mt-1 text-xs text-gray-500">Latest 50 retained records. Observation bytes are original exported totals; the graphs use only this subscription’s attributed portions.</p></div>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Time</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Source</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Destination</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Protocol</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Bytes</th></tr></thead><tbody class="divide-y divide-gray-100">
                    <template x-for="flow in report.latest_flows" :key="flow.id"><tr><td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500" x-text="formatTime(flow.flow_start)"></td><td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-700" x-text="flow.source"></td><td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-700" x-text="flow.destination"></td><td class="px-4 py-3 text-gray-500" x-text="({ 1: 'ICMP', 6: 'TCP', 17: 'UDP', 58: 'ICMPv6' })[flow.protocol] || flow.protocol"></td><td class="whitespace-nowrap px-4 py-3 text-gray-700" x-text="formatBytes(flow.bytes)"></td></tr></template>
                    <tr x-show="!report.latest_flows.length"><td colspan="5" class="px-4 py-8 text-center text-gray-500">No retained flow details in this window.</td></tr>
                </tbody></table></div>
            </div>
        </div>
    </template>
</div>
