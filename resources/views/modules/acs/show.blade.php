@extends('layouts.admin')
@section('title', 'Device details')
@section('content')
<div class="space-y-6">
    <a class="text-sm font-medium text-teal-700" href="{{ route('acs.index') }}">← Customer devices</a>
    <div class="flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-900">{{ $device->manufacturer }} {{ $device->model }}</h1><p class="mt-1 text-sm text-gray-500">{{ $device->serial_number }} · Last contact {{ $device->last_contact_at?->diffForHumans() ?? 'not recorded' }}</p></div>
    @if(auth()->user()->hasPermission('acs.actions'))<form method="POST" action="{{ route('acs.action',$device) }}">@csrf<x-ui.input.hidden name="operation" value="refresh" /><x-ui.button type="submit">Refresh device information</x-ui.button></form>@endif</div>
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>@endif
    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Equipment overview"><dl class="grid grid-cols-2 gap-4 text-sm"><div><dt class="text-gray-500">Firmware</dt><dd class="mt-1 font-medium">{{ $device->firmware ?: 'Not reported' }}</dd></div><div><dt class="text-gray-500">ACS</dt><dd class="mt-1 font-medium">{{ $device->connection->name }}</dd></div><div><dt class="text-gray-500">Customer</dt><dd class="mt-1 font-medium">{{ $device->customer?->full_name ?? 'Unassigned' }}</dd></div><div><dt class="text-gray-500">Subscription</dt><dd class="mt-1 font-medium">@if($device->subscription)<a class="text-teal-700" href="{{ route('subscriptions.show',$device->subscription) }}">{{ $device->subscription->subscription_code }}</a>@else None assigned @endif</dd></div></dl></x-ui.card>
        @if(auth()->user()->hasPermission('acs.write'))<x-ui.card title="Customer assignment" subtitle="Verify equipment ownership before linking it. This does not change device settings."><form method="POST" action="{{ route('acs.assign',$device) }}" class="space-y-4">@csrf @method('PUT')
            <x-input.device-assignment :customers="$customers" :subscriptions="$subscriptions" :customer-id="$device->customer_id" :subscription-id="$device->subscription_id" />
            <x-ui.input.checkbox name="ownership_verified" label="I verified that this device belongs to our ISP and selected customer." :checked="false" />
            <x-ui.button type="submit" variant="brand">Save assignment</x-ui.button>
        </form></x-ui.card>@endif
        @if(auth()->user()->hasPermission('acs.actions'))
        @php($ssidPaths = collect($device->capabilities ?? [])->filter(fn($type,$path) => preg_match('/^(Device\.WiFi\.SSID\.\d+\.SSID|InternetGatewayDevice\.LANDevice\.\d+\.WLANConfiguration\.\d+\.SSID)$/',$path))->mapWithKeys(fn($type,$path)=>[$path=>$path])->all())
        <x-ui.card title="Wi-Fi name" subtitle="Only writable interfaces reported by this device are available.">
            @if($ssidPaths)<form method="POST" action="{{ route('acs.action',$device) }}" class="space-y-4">@csrf<x-ui.input.hidden name="operation" value="wifi" /><x-ui.input.select name="ssid_path" label="Wi-Fi interface" :options="$ssidPaths" :value="old('ssid_path')" required /><x-ui.input.text name="ssid" label="New Wi-Fi name" maxlength="32" required /><x-ui.button type="submit" variant="brand">Update Wi-Fi name</x-ui.button></form>
            @else<p class="text-sm text-gray-500">No supported writable Wi-Fi interface has been discovered. Refresh the device, then synchronize inventory. Availability depends on its RouterOS version and TR-069 package.</p>@endif
        </x-ui.card>
        <x-ui.card title="Reboot equipment" subtitle="The customer's internet connection will briefly disconnect."><form method="POST" action="{{ route('acs.action',$device) }}" class="space-y-4" x-data @submit="if (!confirm('Reboot this customer device? Internet access will briefly disconnect.')) $event.preventDefault()">@csrf<x-ui.input.hidden name="operation" value="reboot" /><x-ui.input.checkbox name="confirmed" label="The customer is ready for a brief interruption." :checked="false" /><x-ui.button type="submit" variant="danger">Reboot device</x-ui.button></form></x-ui.card>
        @endif
    </div>
    @if(auth()->user()->isOwner())
    <x-ui.card title="Provisioning profile" subtitle="Optional. Start with manual provisioning; automation is configured separately in ACS settings.">
        @if($device->profile)<p class="mb-4 text-sm text-gray-600">Assigned: {{ $device->profile->name }} · Revision {{ $device->profile->revision }}</p><form method="POST" action="{{ route('acs.provision',$device) }}" class="mb-5" x-data @submit="if (!confirm('Apply this profile to the assigned active subscription? Connectivity may change.')) $event.preventDefault()">@csrf<x-ui.button type="submit" variant="brand">Apply profile</x-ui.button></form>@endif
        <details><summary class="cursor-pointer text-sm font-medium text-teal-800">Configure a profile for this equipment</summary><form method="POST" action="{{ route('acs.profile',$device) }}" class="mt-5 space-y-4">@csrf
            <x-ui.input.text name="name" label="Profile name" required :value="old('name')" />
            <p class="text-sm text-gray-500">Choose discovered writable string parameters. Supported credential placeholders: <code>@{{pppoe_username}}</code> and <code>@{{pppoe_password}}</code>. Profiles must be tested on this device before enabling automation.</p>
            @php($profilePaths = collect($device->capabilities ?? [])->filter(fn($type,$path) => $type === 'xsd:string' && !preg_match('/ManagementServer|X_MIKROTIK.*(Script|Command)|Password.*ACS/i',$path))->mapWithKeys(fn($type,$path)=>[$path=>$path])->all())
            @for($row=0;$row<4;$row++)<div class="grid gap-3 sm:grid-cols-2"><x-ui.input.select :name="'parameters['.$row.'][path]'" :label="'Parameter '.($row+1)" :options="$profilePaths" placeholder="Unused" /><x-ui.input.password :name="'parameters['.$row.'][value]'" label="Value or credential placeholder" autocomplete="new-password" /></div>@endfor
            <x-ui.button type="submit">Save profile</x-ui.button>
        </form></details>
    </x-ui.card>
    @endif
    <x-ui.card title="Device activity" subtitle="Pending operations wait for device contact. Activity updates automatically.">
    <div x-data="{ changes: [], poller: null, async refresh() { try { const response = await fetch(@js(route('acs.activity',$device)), { headers: { Accept: 'application/json' } }); if (response.ok) { this.changes = (await response.json()).actions; } else { clearInterval(this.poller); } } catch {} }, init() { this.refresh(); this.poller = setInterval(() => this.refresh(), 10000); }, destroy() { clearInterval(this.poller); } }">
    <template x-for="action in changes" :key="action.id"><div class="flex flex-wrap items-center gap-3 border-b border-gray-100 py-3 text-sm"><span class="text-gray-500" x-text="action.requested"></span><span class="font-medium capitalize" x-text="action.operation"></span><span class="text-gray-500" x-text="action.actor"></span><span class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize" x-text="action.status"></span><span class="text-gray-500" x-text="action.message"></span></div></template>
    <div x-show="changes.length === 0" class="py-5 text-sm text-gray-500">No operations requested yet.</div>
    <noscript><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-left text-sm"><thead class="text-gray-500"><tr><th class="px-3 py-3">Requested</th><th class="px-3 py-3">Operation</th><th class="px-3 py-3">By</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Details</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($actions as $action)<tr><td class="px-3 py-3">{{ $action->created_at->diffForHumans() }}</td><td class="px-3 py-3 capitalize">{{ $action->operation }}</td><td class="px-3 py-3">{{ $action->actor?->name ?? 'System' }}</td><td class="px-3 py-3"><span @class(['rounded-full px-2 py-1 text-xs font-medium','bg-green-50 text-green-700'=>$action->status==='completed','bg-red-50 text-red-700'=>in_array($action->status,['failed','uncertain']),'bg-gray-100 text-gray-700'=>!in_array($action->status,['completed','failed','uncertain'])])>{{ ucfirst($action->status) }}</span></td><td class="px-3 py-3 text-gray-500">{{ $action->message }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-gray-500">No operations requested yet.</td></tr>@endforelse</tbody></table></div></noscript></div></x-ui.card>
</div>
@endsection
