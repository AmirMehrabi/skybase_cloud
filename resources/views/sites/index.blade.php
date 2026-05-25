@extends('layouts.admin')

@section('title', 'Sites')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .site-topology-map { min-height: 420px; }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="sitesIndex()" x-init="init()" x-cloak>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sites</h1>
            <p class="text-sm text-gray-500 mt-1">Manage physical locations and network topology</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="refresh()" :disabled="loading" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 disabled:opacity-50">
                <svg class="w-4 h-4" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
            <a href="{{ route('sites.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Site
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <template x-for="card in statCards" :key="card.label">
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-500" x-text="card.label"></p>
                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="card.value"></p>
            </div>
        </template>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 p-6 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Topology Map</h2>
                <p class="text-sm text-gray-500 mt-1">Sites are colored by assigned router health.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-600">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>Online</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>Offline routers</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-gray-400"></span>No routers</span>
            </div>
        </div>
        <div class="relative">
            <div x-ref="map" class="site-topology-map w-full"></div>
            <div x-show="mapSites.length === 0" class="absolute inset-0 flex items-center justify-center bg-white/85 text-sm text-gray-500" style="display: none;">
                No mapped sites yet.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <input type="text" x-model="filters.search" @input="debouncedLoadSites" placeholder="Search by name, code, address..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border">
            </div>
            <select x-model="filters.status" @change="loadSites" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 border bg-white">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Site</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Coordinates</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Routers</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="site in sites" :key="site.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <a :href="urls.show + '/' + site.id" class="text-sm font-medium text-blue-600 hover:text-blue-700" x-text="site.name"></a>
                                    <span class="text-xs text-gray-500" x-text="site.code"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700" x-text="site.address || '—'"></td>
                            <td class="px-6 py-4">
                                <div class="font-mono text-xs text-gray-700" x-text="`${site.latitude.toFixed(5)}, ${site.longitude.toFixed(5)}`"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900" x-text="site.routers_count"></div>
                                <div class="text-xs text-gray-500" x-text="`${site.online_routers_count} online / ${site.offline_routers_count} offline`"></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border capitalize" :class="site.status === 'active' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200'" x-text="site.status"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="urls.show + '/' + site.id" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="View">View</a>
                                    <a :href="urls.edit + '/' + site.id" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg" title="Edit">Edit</a>
                                    <button @click="confirmDelete(site)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="sites.length === 0 && !loading" style="display: none;">
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No sites found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div x-show="loading" class="px-6 py-12 text-center text-sm text-gray-500" style="display: none;">Loading sites...</div>
    </div>

    <div x-show="deleteModal.show" class="fixed inset-0 z-[2000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div class="relative z-[2000] flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 z-[2000] bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="relative z-[2010] inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Site</h3>
                    <p class="text-sm text-gray-500 mt-2">Are you sure you want to delete "<span x-text="deleteModal.site?.name"></span>"? Assigned routers will keep their legacy site text but lose this managed site link.</p>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="deleteSite" :disabled="deleteModal.deleting" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span x-show="!deleteModal.deleting">Delete</span>
                        <span x-show="deleteModal.deleting">Deleting...</span>
                    </button>
                    <button @click="deleteModal.show = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function sitesIndex() {
    return {
        sites: [],
        mapSites: [],
        stats: { total: 0, active: 0, routers: 0, withOfflineRouters: 0 },
        filters: { search: '', status: '' },
        loading: true,
        debounceTimer: null,
        map: null,
        markerLayer: null,
        deleteModal: { show: false, site: null, deleting: false },
        urls: {
            show: '{{ url('sites') }}',
            edit: '{{ url('sites') }}',
            destroy: '{{ url('sites') }}'
        },
        get statCards() {
            return [
                { label: 'Total Sites', value: this.stats.total },
                { label: 'Active Sites', value: this.stats.active },
                { label: 'Assigned Routers', value: this.stats.routers },
                { label: 'With Offline Routers', value: this.stats.withOfflineRouters },
            ];
        },
        init() {
            this.$nextTick(() => this.initMap());
            this.refresh();
        },
        async refresh() {
            await Promise.all([this.loadStats(), this.loadSites(), this.loadMapData()]);
        },
        initMap() {
            if (!window.L || this.map) return;

            this.map = window.L.map(this.$refs.map, { scrollWheelZoom: false }).setView([35.6892, 51.3890], 6);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);
            this.markerLayer = window.L.layerGroup().addTo(this.map);
        },
        async loadSites() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);

                const response = await fetch('{{ route('sites.data') }}?' + params.toString());
                const data = await response.json();
                this.sites = data.sites;
            } finally {
                this.loading = false;
            }
        },
        async loadStats() {
            const response = await fetch('{{ route('sites.stats') }}');
            this.stats = await response.json();
        },
        async loadMapData() {
            const response = await fetch('{{ route('sites.map-data') }}');
            const data = await response.json();
            this.mapSites = data.sites;
            this.renderMarkers();
        },
        renderMarkers() {
            if (!this.map || !this.markerLayer) return;

            this.markerLayer.clearLayers();
            const bounds = [];

            this.mapSites.forEach((site) => {
                const color = this.markerColor(site.health);
                const marker = window.L.circleMarker([site.latitude, site.longitude], {
                    radius: 9,
                    color,
                    fillColor: color,
                    fillOpacity: 0.85,
                    weight: 2
                });

                marker.bindPopup(`
                    <div class="space-y-1">
                        <div class="font-semibold text-gray-900">${this.escapeHtml(site.name)}</div>
                        <div class="text-xs text-gray-500">${this.escapeHtml(site.code)}</div>
                        <div class="text-sm text-gray-700">${site.routers_count} routers</div>
                        <div class="text-xs text-gray-500">${site.online_routers_count} online / ${site.offline_routers_count} offline</div>
                        <div class="pt-2 flex gap-2">
                            <a href="${site.show_url}" class="text-blue-600 text-xs font-medium">View</a>
                            <a href="${site.edit_url}" class="text-green-600 text-xs font-medium">Edit</a>
                        </div>
                    </div>
                `);

                marker.addTo(this.markerLayer);
                bounds.push([site.latitude, site.longitude]);
            });

            if (bounds.length > 0) {
                this.map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
            }
        },
        markerColor(health) {
            if (health === 'degraded') return '#ef4444';
            if (health === 'online') return '#22c55e';
            return '#9ca3af';
        },
        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character]));
        },
        debouncedLoadSites() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.loadSites(), 300);
        },
        confirmDelete(site) {
            this.deleteModal.site = site;
            this.deleteModal.show = true;
        },
        async deleteSite() {
            if (!this.deleteModal.site) return;

            this.deleteModal.deleting = true;
            try {
                const response = await fetch(`${this.urls.destroy}/${this.deleteModal.site.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.deleteModal.show = false;
                    await this.refresh();
                } else {
                    alert('Error deleting site. Please try again.');
                }
            } finally {
                this.deleteModal.deleting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
