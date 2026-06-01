<?php

namespace App\Http\Controllers;

use App\Models\TenantNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = TenantNotification::query()
            ->forTenant((string) ($user->tenant_id ?? tenant_id()))
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->visible()
            ->when($request->input('status') === 'unread', fn ($query) => $query->unread())
            ->when($request->filled('category'), fn ($query) => $query->where('data->category', $request->input('category')))
            ->when($request->filled('severity'), fn ($query) => $query->where('data->severity', $request->input('severity')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'categories' => ['billing', 'subscription', 'support', 'network', 'system', 'usage'],
            'severities' => ['info', 'success', 'warning', 'critical'],
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->notificationForUser($request, $notification);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        TenantNotification::query()
            ->forTenant((string) ($request->user()->tenant_id ?? tenant_id()))
            ->where('notifiable_type', $request->user()->getMorphClass())
            ->where('notifiable_id', $request->user()->id)
            ->visible()
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function archive(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->notificationForUser($request, $notification);
        $notification->forceFill(['archived_at' => now()])->save();

        return back()->with('success', 'Notification archived.');
    }

    private function notificationForUser(Request $request, string $notification): TenantNotification
    {
        $notification = TenantNotification::query()->where('id', $notification)->visible()->firstOrFail();

        abort_unless(
            (string) $notification->tenant_id === (string) ($request->user()->tenant_id ?? tenant_id())
            && $notification->notifiable_type === $request->user()->getMorphClass()
            && (int) $notification->notifiable_id === (int) $request->user()->id,
            403
        );

        return $notification;
    }
}
