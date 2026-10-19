@extends('layouts.admin')
@section('title', 'Message template')
@section('content')
<x-communication-nav />
<h1 class="mb-6 text-2xl font-semibold text-gray-900">{{ $template->exists ? 'Edit template & rule' : 'New template & rule' }}</h1>
<form method="POST" action="{{ $template->exists ? route('communications.update', $template) : route('communications.store') }}" class="space-y-6">
@csrf @if($template->exists) @method('PUT') @endif
<x-ui.card title="Delivery rule"><div class="grid gap-5 md:grid-cols-2">
<x-ui.input.text name="name" label="Name" :value="old('name', $template->name)" required />
<x-ui.input.select name="event_key" label="Application event" :options="$events" :value="old('event_key', $template->event_key)" placeholder="Manual campaign template" />
<x-ui.input.select name="recipient_type" label="Recipient type" :options="['customer'=>'Customer','admin'=>'Staff','both'=>'Customer and staff']" :value="old('recipient_type', $template->recipient_type)" required />
<x-ui.input.checkbox name="enabled" label="Enabled" :checked="old('enabled', $template->enabled)" />
</div><div class="mt-5 flex gap-5">@foreach(['email'=>'Email','sms'=>'SMS'] as $key=>$label)<x-ui.input.checkbox name="channels[]" :checked-only="true" :id="'channel-'.$key" :value="$key" :label="$label" :checked="in_array($key, old('channels', $template->channels ?? []))" />@endforeach</div>
<p class="mt-4 text-sm text-gray-500">External sends also require enabled tenant channels and recipient preferences. Router incident contacts are configured below.</p></x-ui.card>
<x-ui.card title="Message content"><div class="space-y-5">
<x-ui.input.text name="subject" label="Email subject" :value="old('subject', $template->subject)" />
<x-ui.input.textarea name="email_body" label="Email body (plain text)" :value="old('email_body', $template->email_body)" rows="6" />
<x-ui.input.textarea name="sms_body" label="SMS body" :value="old('sms_body', $template->sms_body)" rows="4" />
<p class="text-sm text-gray-500">Insert variables with double braces, for example @verbatim {{customer_name}} or {{body}} @endverbatim. Available variables: {{ implode(', ', App\Services\MessageTemplateService::VARIABLES) }}.</p>
</div></x-ui.card>
<x-ui.card title="Kavenegar Lookup" subtitle="Use an approved provider template. Token values are validated and are never silently changed."><div class="grid gap-5 md:grid-cols-3">
<x-ui.input.select name="sms_mode" label="SMS mode" :options="['plain'=>'Ordinary SMS','lookup'=>'Kavenegar Lookup']" :value="old('sms_mode', $template->sms_mode)" required />
<x-ui.input.text name="lookup_template" label="Approved template name" :value="old('lookup_template', $template->lookup_template)" />
@foreach(['token','token2','token3','token10','token20'] as $token)<x-ui.input.select :name="'lookup_tokens['.$token.']'" :label="$token.' variable'" :options="array_combine(App\Services\MessageTemplateService::VARIABLES, App\Services\MessageTemplateService::VARIABLES)" :value="old('lookup_tokens.'.$token, $template->lookup_tokens[$token] ?? '')" placeholder="Unused" />@endforeach
</div></x-ui.card>
<x-ui.card title="Incident escalation contacts" subtitle="Used by router offline/recovery rules. One contact per line: channel, destination, escalation stage (0 for initial).">
<x-ui.input.textarea name="recipient_lines" label="Contacts" :value="old('recipient_lines', collect($template->recipients)->map(fn($contact) => $contact['channel'].', '.$contact['destination'].', '.$contact['stage'])->implode(PHP_EOL))" placeholder="email, noc@example.com, 0" rows="4" />
</x-ui.card>
<div class="flex justify-end"><x-ui.button type="submit">Save template</x-ui.button></div>
</form>
@if($template->exists)
<div class="mt-6"><x-ui.card title="Send a real test" subtitle="Save changes first. A test uses sample values and can incur provider charges."><form method="POST" action="{{ route('communications.test', $template) }}" class="flex flex-wrap items-end gap-4">@csrf<x-ui.input.hidden name="action" value="send" /><x-ui.input.select name="channel" label="Channel" :options="array_combine($template->channels, $template->channels)" required /><x-ui.input.text name="destination" label="Test email or phone" required /><x-ui.button type="submit" variant="secondary">Queue test message</x-ui.button></form></x-ui.card></div>
@endif
@endsection
