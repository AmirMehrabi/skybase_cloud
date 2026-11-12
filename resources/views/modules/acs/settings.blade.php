@extends('layouts.admin')
@section('title', 'ACS integration')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4"><div><h1 class="text-2xl font-bold text-gray-900">ACS integration</h1><p class="mt-1 text-sm text-gray-500">Connect your equipment once. Manage it alongside your customers.</p></div><a href="{{ route('acs.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-teal-800 hover:bg-gray-50">Device inventory</a></div>
    <x-ui.card title="1. Connect GenieACS" subtitle="Use the private management endpoint approved for your ISP. Device enrollment uses a separate HTTPS address.">
        <form method="POST" action="{{ route('acs.save') }}" class="space-y-5">@csrf @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input.text name="name" label="Connection name" :value="old('name', $connection?->name ?? 'My GenieACS')" :error="$errors->first('name')" required />
                <x-ui.input.select name="base_url" label="Management endpoint" :options="$endpoints" :value="old('base_url', $connection?->base_url)" :error="$errors->first('base_url')" required />
                <x-ui.input.text name="username" label="Management username" :value="old('username', $connection?->username)" :error="$errors->first('username')" hint="Leave blank when using the approved local endpoint." />
                <x-ui.input.password name="password" label="Management password" autocomplete="new-password" :placeholder="$connection ? 'Leave blank to keep saved password' : 'Optional for a local endpoint'" :error="$errors->first('password')" />
            </div>
            <x-ui.input.text name="cwmp_url" label="Device enrollment URL" placeholder="https://your-acs.example.com/" :value="old('cwmp_url', $connection?->cwmp_url)" :error="$errors->first('cwmp_url')" hint="The public HTTPS address your MikroTik connects to. This is not the management endpoint." />
            <x-ui.input.toggle name="automation_enabled" label="Automatic provisioning" description="Apply assigned profiles when active subscriptions or their credentials change. Leave off until manual provisioning has been tested." :checked="old('automation_enabled', $connection?->automation_enabled ?? false)" />
            <x-ui.button type="submit" variant="brand">Save connection</x-ui.button>
        </form>
    </x-ui.card>
    <x-ui.card title="2. Check the connection" subtitle="Confirm SkyBase can reach your ACS before connecting equipment.">
        <div class="flex flex-wrap items-center justify-between gap-4"><p class="text-sm text-gray-600">{{ $connection ? ucfirst($connection->health) : 'Save your connection first' }} @if($connection?->last_tested_at) · Tested {{ $connection->last_tested_at->diffForHumans() }} @endif</p>
        @if($connection)<form method="POST" action="{{ route('acs.test') }}" x-data="{ busy: false }" @submit="busy = true">@csrf<x-ui.button type="submit" x-bind:disabled="busy"><span x-text="busy ? 'Checking…' : 'Test connection'">Test connection</span></x-ui.button></form>@endif</div>
    </x-ui.card>
    <x-ui.card title="3. Connect your MikroTik hAP lite" subtitle="Start with inventory and refresh. Make configuration changes only after the device reports its supported parameters.">
        <ol class="list-decimal space-y-3 pl-5 text-sm text-gray-600"><li>Check RouterOS version, architecture and free storage. Install the matching <strong>tr069-client</strong> package if needed; do not upgrade or reset the router just to enroll it.</li><li>In WinBox, open <strong>TR069</strong>, enable the client and enter the enrollment URL below with the device credentials supplied by your administrator.</li><li>Enable periodic informs, initially every 5 minutes. Verify the router clock and HTTPS certificate trust.</li><li>Discover devices, verify the serial number, then assign the equipment to its customer.</li></ol>
        <div class="mt-4 break-all rounded-lg bg-gray-50 p-4 font-mono text-sm">{{ $connection?->cwmp_url ?: 'Save your device enrollment URL above.' }}</div>
        <p class="mt-3 text-xs text-gray-500">A CPE behind NAT can contact the ACS normally. Commands may wait for its next periodic inform if an immediate connection request cannot reach it.</p>
        @if($connection)<form method="POST" action="{{ route('acs.sync') }}" class="mt-5">@csrf<x-ui.button type="submit" variant="brand">Discover devices</x-ui.button></form>@endif
    </x-ui.card>
</div>
@endsection
