@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <x-communication-nav />
    <x-ui.page-header title="Notifications" description="Your operational alerts, support updates and billing messages, in one inbox." icon="bell">
        @if(auth()->user()->canAccessRoute('notifications.read-all'))<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<x-ui.button type="submit" variant="secondary">Mark all read</x-ui.button></form>@endif
    </x-ui.page-header>

    <form method="GET" action="{{ route('notifications.index') }}" class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <x-ui.input.select name="status" label="Status" :options="['unread' => 'Unread']" :value="request('status')" placeholder="All statuses" />
        <x-ui.input.select name="category" label="Category" :options="collect($categories)->mapWithKeys(fn($category) => [$category => ucfirst($category)])->all()" :value="request('category')" placeholder="All categories" />
        <x-ui.input.select name="severity" label="Severity" :options="collect($severities)->mapWithKeys(fn($severity) => [$severity => ucfirst($severity)])->all()" :value="request('severity')" placeholder="All severities" />
        <div class="flex items-end"><x-ui.button type="submit" variant="brand" class="w-full">Apply filters</x-ui.button></div>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $severity = $data['severity'] ?? 'info';
                $badge = match ($severity) {
                    'critical' => 'bg-red-100 text-red-700',
                    'warning' => 'bg-amber-100 text-amber-700',
                    'success' => 'bg-emerald-100 text-emerald-700',
                    default => 'bg-blue-100 text-blue-700',
                };
            @endphp
            <div class="flex flex-col gap-4 border-b border-gray-100 p-5 last:border-b-0 md:flex-row md:items-start md:justify-between {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/40' }}">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold text-gray-950">{{ $data['title'] ?? 'Notification' }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ ucfirst($severity) }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ ucfirst($data['category'] ?? 'system') }}</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">{{ $data['body'] ?? '' }}</p>
                    <p class="mt-2 text-xs text-gray-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if(! empty($data['action_url']))
                        <a href="{{ $data['action_url'] }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Open</a>
                    @endif
                    @if(! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', ['notification' => $notification->id]) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">Mark read</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('notifications.archive', ['notification' => $notification->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-50">Archive</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center">
                <h2 class="text-base font-semibold text-gray-900">No notifications</h2>
                <p class="mt-1 text-sm text-gray-500">New operational, billing, and support updates will appear here.</p>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
@endsection
