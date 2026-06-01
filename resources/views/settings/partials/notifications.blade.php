<form action="{{ route('settings.update.notifications') }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <x-ui.card title="Notification Delivery" subtitle="Control tenant-wide notification behavior. Email and SMS preferences are stored for future delivery, but v1 sends in-app notifications only.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-4">
                <input type="hidden" name="notifications_enabled" value="0">
                <input type="checkbox" name="notifications_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('notifications_enabled', $notificationSettings['enabled']))>
                <span>
                    <span class="block text-sm font-medium text-gray-900">Enable optional notifications</span>
                    <span class="block text-xs text-gray-500">Critical admin alerts still appear in-app.</span>
                </span>
            </label>

            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-4">
                <input type="hidden" name="in_app_enabled" value="0">
                <input type="checkbox" name="in_app_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('in_app_enabled', $notificationSettings['channels']['in_app']))>
                <span>
                    <span class="block text-sm font-medium text-gray-900">In-app notifications</span>
                    <span class="block text-xs text-gray-500">Show notifications in admin and customer portals.</span>
                </span>
            </label>

            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 opacity-80">
                <input type="hidden" name="email_enabled" value="0">
                <input type="checkbox" name="email_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('email_enabled', $notificationSettings['channels']['email']))>
                <span>
                    <span class="block text-sm font-medium text-gray-900">Email preferences</span>
                    <span class="block text-xs text-gray-500">Stored now; external sending comes later.</span>
                </span>
            </label>

            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 opacity-80">
                <input type="hidden" name="sms_enabled" value="0">
                <input type="checkbox" name="sms_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('sms_enabled', $notificationSettings['channels']['sms']))>
                <span>
                    <span class="block text-sm font-medium text-gray-900">SMS preferences</span>
                    <span class="block text-xs text-gray-500">Stored now; external sending comes later.</span>
                </span>
            </label>
        </div>
    </x-ui.card>

    <x-ui.card title="Notification Events" subtitle="Choose which optional events create notifications. Critical operational events remain mandatory for admins.">
        <div class="divide-y divide-gray-100">
            @foreach($notificationEvents as $eventKey => $event)
                <label class="flex items-center justify-between gap-4 py-4">
                    <span>
                        <span class="block text-sm font-medium text-gray-900">{{ $event['label'] }}</span>
                        <span class="block text-xs text-gray-500">{{ ucfirst($event['category']) }} · {{ ucfirst($event['severity']) }}{{ $event['critical'] ? ' · Mandatory for admins' : '' }}</span>
                    </span>
                    <span class="flex items-center gap-2">
                        <input type="hidden" name="events[{{ $eventKey }}]" value="0">
                        <input type="checkbox" name="events[{{ $eventKey }}]" value="1" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old("events.{$eventKey}", $notificationSettings['events'][$eventKey] ?? true)) @disabled($event['critical'])>
                        @if($event['critical'])
                            <input type="hidden" name="events[{{ $eventKey }}]" value="1">
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
    </x-ui.card>

    <div class="flex justify-end">
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">Save Notification Settings</button>
    </div>
</form>
