<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\TenantNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user('customer');

        $notifications = TenantNotification::query()
            ->forTenant((string) $customer->tenant_id)
            ->where('notifiable_type', $customer->getMorphClass())
            ->where('notifiable_id', $customer->id)
            ->visible()
            ->when($request->input('status') === 'unread', fn ($query) => $query->unread())
            ->when($request->filled('category'), fn ($query) => $query->where('data->category', $request->input('category')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('customer.notifications.index', [
            'notifications' => $notifications,
            'categories' => ['service', 'billing', 'support', 'usage', 'account'],
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->notificationForCustomer($request, $notification);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        TenantNotification::query()
            ->forTenant((string) $customer->tenant_id)
            ->where('notifiable_type', $customer->getMorphClass())
            ->where('notifiable_id', $customer->id)
            ->visible()
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function archive(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->notificationForCustomer($request, $notification);
        $notification->forceFill(['archived_at' => now()])->save();

        return back()->with('success', 'Notification archived.');
    }

    private function notificationForCustomer(Request $request, string $notification): TenantNotification
    {
        $customer = $request->user('customer');
        $notification = TenantNotification::query()->where('id', $notification)->visible()->firstOrFail();

        abort_unless(
            (string) $notification->tenant_id === (string) $customer->tenant_id
            && $notification->notifiable_type === $customer->getMorphClass()
            && (int) $notification->notifiable_id === (int) $customer->id,
            403
        );

        return $notification;
    }
}
