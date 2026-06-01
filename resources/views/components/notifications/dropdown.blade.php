@props(['guard' => 'web'])

@php
    $recipient = $guard === 'customer' ? auth('customer')->user() : auth()->user();
    $isCustomer = $guard === 'customer';
    $notificationsRoute = $isCustomer ? route('customer.notifications.index') : route('notifications.index');
    $readAllRoute = $isCustomer ? route('customer.notifications.read-all') : route('notifications.read-all');
    $readRouteName = $isCustomer ? 'customer.notifications.read' : 'notifications.read';

    $notificationQuery = $recipient
        ? \App\Models\TenantNotification::query()
            ->where('tenant_id', $recipient->tenant_id)
            ->where('notifiable_type', $recipient->getMorphClass())
            ->where('notifiable_id', $recipient->id)
            ->visible()
        : null;

    $unreadNotificationsCount = $notificationQuery ? (clone $notificationQuery)->unread()->count() : 0;
    $latestNotifications = $notificationQuery ? (clone $notificationQuery)->latest()->limit(6)->get() : collect();
@endphp

<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = ! open" class="relative rounded-lg p-2 text-slate-500 hover:bg-[#fbf7ed] hover:text-slate-950">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        @if($unreadNotificationsCount > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white ring-2 ring-white">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" class="absolute {{ $isCustomer ? 'left-0' : 'right-0' }} z-50 mt-2 w-80 overflow-hidden rounded-xl border border-slate-900/10 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-900/10 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-slate-950">Notifications</p>
                <p class="text-xs text-slate-500">{{ $unreadNotificationsCount }} unread</p>
            </div>
            @if($unreadNotificationsCount > 0)
                <form method="POST" action="{{ $readAllRoute }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-700">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($latestNotifications as $notification)
                @php
                    $data = $notification->data;
                    $severity = $data['severity'] ?? 'info';
                    $dot = match ($severity) {
                        'critical' => 'bg-red-500',
                        'warning' => 'bg-amber-500',
                        'success' => 'bg-emerald-500',
                        default => 'bg-blue-500',
                    };
                @endphp
                <div class="border-b border-slate-900/5 px-4 py-3 last:border-b-0 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/60' }}">
                    <div class="flex gap-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Notification' }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-600">{{ $data['body'] ?? '' }}</p>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                                @if(! $notification->read_at)
                                    <form method="POST" action="{{ route($readRouteName, ['notification' => $notification->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-[11px] font-medium text-blue-600 hover:text-blue-700">Read</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500">No notifications yet.</div>
            @endforelse
        </div>

        <a href="{{ $notificationsRoute }}" class="block border-t border-slate-900/10 px-4 py-3 text-center text-sm font-medium text-slate-700 hover:bg-[#fbf7ed]">View all notifications</a>
    </div>
</div>
