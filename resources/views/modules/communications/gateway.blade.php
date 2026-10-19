@extends('layouts.admin')
@section('title', 'SMS gateway settings')
@section('content')
<x-communication-nav /><h1 class="mb-2 text-2xl font-semibold text-gray-900">SMS gateway</h1><p class="mb-6 text-sm text-gray-500">Configure one active provider per tenant. Saved secrets are encrypted and never displayed.</p>
<form method="POST" action="{{ route('settings.update.sms') }}" class="space-y-6">@csrf @method('PUT')
<x-ui.card title="Delivery settings"><div class="grid gap-5 md:grid-cols-2"><x-ui.input.select name="provider" label="Active gateway" :options="['none'=>'Disabled','twilio'=>'Twilio','smsportal'=>'SMSPortal','kavenegar'=>'Kavenegar']" :value="old('provider', $settings['provider'])" required /><x-ui.input.text name="rate_per_minute" type="number" label="Maximum sends per minute / channel" :value="old('rate_per_minute', $settings['rate_per_minute'])" required /></div></x-ui.card>
@foreach(['twilio'=>['sid'=>'Account SID','token'=>'Authentication token','sender'=>'Sender number'], 'smsportal'=>['client_id'=>'Client ID','secret'=>'API secret'], 'kavenegar'=>['api_key'=>'API key','sender'=>'Sender number','credentials_template'=>'Credentials Lookup template','reset_template'=>'Password reset Lookup template']] as $provider=>$fields)
<x-ui.card :title="ucfirst($provider)"><div class="grid gap-5 md:grid-cols-3">@foreach($fields as $field=>$label)
@if(in_array($field,['token','secret','api_key']))<x-ui.input.password :name="$provider.'['.$field.']'" :label="$label" placeholder="Leave blank to keep saved secret" :hint="filled($settings[$provider][$field]) ? 'A secret is saved.' : 'No secret saved.'" />
@else<x-ui.input.text :name="$provider.'['.$field.']'" :label="$label" :value="old($provider.'.'.$field, $settings[$provider][$field])" />@endif
@endforeach</div>@if($provider==='smsportal')<div class="mt-4"><x-ui.input.checkbox name="smsportal[test_mode]" label="Test mode (simulate sends without delivery)" :checked="old('smsportal.test_mode', $settings['smsportal']['test_mode'])" /></div>@endif</x-ui.card>
@endforeach
<div class="flex justify-end"><x-ui.button type="submit">Save gateway</x-ui.button></div></form>
<div class="mt-6 space-y-5"><x-ui.card title="Test saved gateway" subtitle="Connection testing checks API credentials. Sending a test creates a real delivery and may incur charges.">
<form method="POST" action="{{ route('settings.test.sms') }}" class="mb-5">@csrf<x-ui.input.hidden name="action" value="connection" /><x-ui.button type="submit" variant="secondary">Test connection</x-ui.button></form>
<form method="POST" action="{{ route('settings.test.sms') }}" class="grid items-end gap-4 md:grid-cols-3">@csrf<x-ui.input.hidden name="action" value="send" /><x-ui.input.text name="destination" label="Phone with country code" placeholder="+989121234567" required /><x-ui.input.text name="message" label="Test message" value="SkyBase SMS connection test" required /><x-ui.button type="submit">Send test SMS</x-ui.button></form>
</x-ui.card></div>
@endsection
