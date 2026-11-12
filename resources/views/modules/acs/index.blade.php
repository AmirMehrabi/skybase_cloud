@extends('layouts.admin')
@section('title', 'Customer Devices')
@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-900">Customer Devices</h1><p class="mt-1 text-sm text-gray-500">Customer equipment, assignments and remote support.</p></div>
    @if(auth()->user()->isOwner())<div class="flex gap-3"><a href="{{ route('acs.settings') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-teal-800 hover:bg-gray-50">ACS settings</a>@if($connection)<form method="POST" action="{{ route('acs.sync') }}" x-data="{ busy: false }" @submit="busy = true">@csrf<x-ui.button type="submit" variant="brand" x-bind:disabled="busy"><span x-text="busy ? 'Queuing…' : 'Discover & sync'">Discover & sync</span></x-ui.button></form>@endif</div>@endif</div>
    @if(!$connection)
    <x-ui.card title="Connect your first ACS" subtitle="Your existing subscriptions continue working as usual. Connect GenieACS when you are ready to manage customer equipment.">
        @if(auth()->user()->isOwner())<a href="{{ route('acs.settings') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-teal-800 hover:bg-gray-50">Set up GenieACS</a>@else<p class="text-sm text-gray-500">Ask your workspace owner to connect GenieACS.</p>@endif
    </x-ui.card>
    @else
    <x-ui.card>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4"><p class="text-sm text-gray-500">{{ $connection->name }} · {{ ucfirst($connection->health) }} · {{ $connection->last_synced_at ? 'Synced '.$connection->last_synced_at->diffForHumans() : 'Not synced yet' }}</p>
        <form method="GET" class="flex items-end gap-3"><x-ui.input.text name="search" label="Find equipment" placeholder="Serial number or model" :value="request('search')" /><x-ui.button type="submit">Search</x-ui.button></form></div>
        @if(request()->hasAny(['customer','subscription']))<p class="mb-4 text-sm text-gray-500">Showing equipment for this {{ request()->has('subscription') ? 'subscription' : 'customer' }}. <a class="text-teal-700 underline" href="{{ route('acs.index') }}">View all devices</a></p>@endif
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-left text-sm"><thead class="text-gray-500"><tr><th class="px-4 py-3">Equipment</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Subscription</th><th class="px-4 py-3">Last contact</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-gray-100">
        @forelse($devices as $device)<tr><td class="px-4 py-4"><a class="font-semibold text-teal-800" href="{{ route('acs.show',$device) }}">{{ $device->manufacturer }} {{ $device->model ?: 'Unknown model' }}</a><div class="mt-1 text-xs text-gray-500">{{ $device->serial_number }}</div></td><td class="px-4 py-4">{{ $device->customer?->full_name ?? 'Unassigned' }}</td><td class="px-4 py-4">{{ $device->subscription?->subscription_code ?? 'No subscription assigned' }}</td><td class="px-4 py-4">{{ $device->last_contact_at?->diffForHumans() ?? 'No contact recorded' }}</td><td class="px-4 py-4"><a class="text-teal-700" href="{{ route('acs.show',$device) }}">Manage →</a></td></tr>
        @empty<tr><td colspan="5" class="px-4 py-12 text-center"><p class="font-medium text-gray-900">No devices to show yet</p><p class="mt-2 text-gray-500">Connect your CPE to the ACS, then discover and assign it. A subscription does not need a device to work.</p></td></tr>@endforelse
        </tbody></table></div><div class="mt-5">{{ $devices->links() }}</div>
    </x-ui.card>
    @endif
</div>
@endsection
