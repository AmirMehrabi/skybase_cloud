<form action="{{ route('settings.update.notifications') }}" method="POST" class="space-y-6">
    @csrf @method('PUT')
    <x-ui.card title="Delivery channels" subtitle="Set workspace defaults. Individual recipient preferences and enabled message rules also apply.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.input.toggle name="notifications_enabled" label="Enable optional notifications" description="Allow event-based updates for your staff and customers. Critical staff alerts stay available in-app." :checked="old('notifications_enabled', $notificationSettings['enabled'])" />
            <x-ui.input.toggle name="in_app_enabled" label="In-app notifications" description="Show updates in the staff and customer notification inboxes." :checked="old('in_app_enabled', $notificationSettings['channels']['in_app'])" />
            <x-ui.input.toggle name="email_enabled" label="Email delivery" description="Send enabled rules through your configured outgoing email connection." :checked="old('email_enabled', $notificationSettings['channels']['email'])" />
            <x-ui.input.toggle name="sms_enabled" label="SMS delivery" description="Send enabled rules through your active SMS gateway." :checked="old('sms_enabled', $notificationSettings['channels']['sms'])" />
        </div>
        <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-4 text-xs font-semibold text-teal-800">
            <a href="{{ route('settings.section', ['section' => 'email']) }}" class="inline-flex items-center gap-1 hover:underline">Email connection <x-ui.icon name="arrow-up-right" class="size-3.5" /></a>
            <a href="{{ route('settings.sms') }}" class="inline-flex items-center gap-1 hover:underline">SMS gateway <x-ui.icon name="arrow-up-right" class="size-3.5" /></a>
            @if(auth()->user()->canAccessRoute('communications.index'))<a href="{{ route('communications.index') }}" class="inline-flex items-center gap-1 hover:underline">Templates & rules <x-ui.icon name="arrow-up-right" class="size-3.5" /></a>@endif
        </div>
    </x-ui.card>
    @foreach(collect($notificationEvents)->groupBy('category', preserveKeys: true) as $category => $events)
        <x-ui.card :title="ucfirst($category).' events'" subtitle="Choose the updates relevant to your workspace.">
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($events as $eventKey => $event)
                    <x-ui.input.toggle :name="'events['.$eventKey.']'" :label="$event['label']" :description="$event['critical'] ? 'Critical alert · always enabled for staff in-app.' : ucfirst($event['severity']).' priority'" :checked="old('events.'.$eventKey, $notificationSettings['events'][$eventKey] ?? true)" :disabled="$event['critical']" />
                @endforeach
            </div>
        </x-ui.card>
    @endforeach
    <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-5 py-4"><p class="text-xs text-slate-500">Changes apply to this workspace.</p><x-ui.button type="submit" variant="brand">Save preferences</x-ui.button></div>
</form>
