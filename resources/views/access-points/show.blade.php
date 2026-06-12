@extends('layouts.admin')

@section('title', $accessPoint->name ?? 'Access Point Details')

@section('content')
<div class="space-y-6" x-data="{ deleteModal: { show: false } }">
    <!-- Top Header -->
    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $accessPoint->name }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border
                            {{ match($accessPoint->status) {
                                'online' => 'bg-green-100 text-green-800 border-green-200',
                                'offline' => 'bg-red-100 text-red-800 border-red-200',
                                'maintenance' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200',
                            } }}">
                            {{ ucfirst($accessPoint->status) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                        @if($accessPoint->mac_address)
                            <span>{{ $accessPoint->mac_address }}</span>
                        @endif
                        @if($accessPoint->model)
                            <span>{{ $accessPoint->model }}</span>
                        @endif
                        @if($accessPoint->frequency_band)
                            <span>{{ $accessPoint->frequency_band }}</span>
                        @endif
                        @if($accessPoint->ssid)
                            <span>SSID: {{ $accessPoint->ssid }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('access-points.edit', $accessPoint) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </a>
                <button @click="deleteModal.show = true" class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 text-red-700 border border-red-200 rounded-lg text-sm font-medium hover:bg-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Status</span>
                <svg class="w-5 h-5 {{ $accessPoint->status === 'online' ? 'text-green-500' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ ucfirst($accessPoint->status) }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Connected Clients</span>
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $accessPoint->connected_clients }} / {{ $accessPoint->max_clients }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Frequency Band</span>
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                </svg>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $accessPoint->frequency_band ?? '—' }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Site</span>
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                </svg>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $accessPoint->siteRecord?->name ?? '—' }}</p>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Device Information -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Device Information</h3>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Vendor</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->vendor }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Model</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->model ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">MAC Address</dt>
                    <dd class="text-sm font-medium text-gray-900 font-mono">{{ $accessPoint->mac_address }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Serial Number</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->serial_number ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Firmware</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->firmware_version ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Last Status Check</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->last_status_checked_at?->diffForHumans() ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Network & Physical -->
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Network & Physical</h3>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">IP Address</dt>
                    <dd class="text-sm font-medium text-gray-900 font-mono">{{ $accessPoint->ip_address ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">SSID</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->ssid ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Channel</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->channel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">TX Power</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->tx_power ? $accessPoint->tx_power . ' dBm' : '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Antenna</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->antenna_type ? $accessPoint->antenna_type . ($accessPoint->antenna_gain ? ' (' . $accessPoint->antenna_gain . ' dBi)' : '') : '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Height</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->height_meters ? $accessPoint->height_meters . 'm' : '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Azimuth / Coverage</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->azimuth ? $accessPoint->azimuth . '°' : '—' }} / {{ $accessPoint->coverage_angle ? $accessPoint->coverage_angle . '°' : '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Parent Router</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $accessPoint->router?->name ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Notes -->
    @if($accessPoint->notes)
        <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $accessPoint->notes }}</p>
        </div>
    @endif

    <!-- Connected Subscriptions -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Connected Subscriptions ({{ $accessPoint->subscriptions->count() }})</h3>
        </div>
        @if($accessPoint->subscriptions->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm text-gray-500">No subscriptions connected to this access point.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subscription</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($accessPoint->subscriptions as $subscription)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $subscription->customer?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $subscription->subscription_code ?? $subscription->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Delete Modal -->
    <x-ui.delete-modal
        title="Delete Access Point"
        name="'{{ $accessPoint->name }}'"
        confirm-action="window.location.href = '{{ route('access-points.index') }}'"
    />
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deleteConfirm', () => ({
        async confirmDelete() {
            try {
                const response = await fetch('{{ route('access-points.destroy', $accessPoint) }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    window.location.href = '{{ route('access-points.index') }}';
                }
            } catch (error) {
                alert('Error deleting access point.');
            }
        }
    }));
});
</script>
@endpush
@endsection
