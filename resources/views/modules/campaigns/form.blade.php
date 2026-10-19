@extends('layouts.admin')
@section('title', $campaign->exists ? 'Edit campaign' : 'New campaign')
@section('content')
<x-communication-nav />
<h1 class="mb-2 text-2xl font-semibold text-gray-900">{{ $campaign->exists ? 'Edit campaign' : 'New campaign' }}</h1>
<p class="mb-6 text-sm text-gray-500">Choose an audience and message. You can review exclusions and personalized previews before sending.</p>
@php($filters = old('filters', request('audience') ? $audiences->firstWhere('id', request('audience'))?->filters ?? $campaign->audience_filters : $campaign->audience_filters))
<form method="POST" action="{{ $campaign->exists ? route('campaigns.update', $campaign) : route('campaigns.store') }}" class="space-y-6">
@csrf @if($campaign->exists) @method('PUT') @endif
<x-ui.card title="1. Campaign details">
<div class="grid gap-5 md:grid-cols-2">
<x-ui.input.text name="name" label="Campaign name" :value="old('name', $campaign->name)" required />
<x-ui.input.select name="purpose" label="Purpose" :options="['service' => 'Service announcement', 'promotional' => 'Promotion']" :value="old('purpose', $campaign->purpose)" required />
</div><div class="mt-5 flex gap-5">@foreach(['email' => 'Email', 'sms' => 'SMS'] as $key => $label)<x-ui.input.checkbox name="channels[]" :checked-only="true" :id="'channel-'.$key" :value="$key" :label="$label" :checked="in_array($key, old('channels', $campaign->channels ?? []))" />@endforeach</div>
</x-ui.card>
<x-ui.card title="2. Audience" subtitle="Customers must match every non-empty filter. Subscription filters must match the same subscription.">
@if($audiences->isNotEmpty())<div class="mb-4 flex flex-wrap gap-2 text-sm"><span class="text-gray-500">Saved audiences:</span>@foreach($audiences as $audience)<a class="text-teal-800 underline" href="{{ route('campaigns.create', ['audience' => $audience->id]) }}">{{ $audience->name }}</a>@endforeach</div>@endif
<div class="grid gap-5 md:grid-cols-3">
<x-ui.input.text name="filters[search]" label="Customer search" :value="$filters['search'] ?? ''" />
<x-ui.input.select name="filters[status]" label="Customer status" :options="['active'=>'Active','inactive'=>'Inactive','suspended'=>'Suspended']" :value="$filters['status'] ?? ''" placeholder="All statuses" />
<x-ui.input.select name="filters[router_id]" label="Router" :options="$routers" :value="$filters['router_id'] ?? ''" placeholder="All routers" />
<x-ui.input.select name="filters[plan_id]" label="Service plan" :options="$plans" :value="$filters['plan_id'] ?? ''" placeholder="All plans" />
<x-ui.input.text name="filters[site]" label="Site" :value="$filters['site'] ?? ''" />
<x-ui.input.select name="filters[subscription_status]" label="Subscription status" :options="['pending'=>'Pending','active'=>'Active','suspended'=>'Suspended','cancelled'=>'Cancelled']" :value="$filters['subscription_status'] ?? ''" placeholder="All statuses" />
<x-ui.input.select name="filters[connection_status]" label="Connection" :options="['online'=>'Online','offline'=>'Offline','unknown'=>'Unknown']" :value="$filters['connection_status'] ?? ''" placeholder="Any connection" />
<x-ui.input.text name="filters[customer_ids]" label="Specific customer IDs (optional)" :value="implode(',', $filters['customer_ids'] ?? [])" hint="Comma-separated IDs. Leave blank to use the filters above." />
<x-ui.input.select name="audience_mode" label="Scheduled audience" :options="['snapshot'=>'Fix audience when draft is saved','dynamic'=>'Re-evaluate filters at send time']" :value="old('audience_mode', $campaign->audience_mode)" required />
<x-ui.input.text name="save_audience_name" label="Save these filters as (optional)" :value="old('save_audience_name')" />
</div></x-ui.card>
<x-ui.card title="3. Message" subtitle="Choose a template to copy its content on save, or write separate email and SMS messages below.">
<div class="space-y-5">
<x-ui.input.select name="template_id" label="Reusable template" :options="$templates" placeholder="Write a custom message" :value="old('template_id')" />
<x-ui.input.text name="subject" label="Email subject" :value="old('subject', $campaign->content['subject'] ?? '')" />
<x-ui.input.textarea name="email_body" label="Email message (plain text)" :value="old('email_body', $campaign->content['email_body'] ?? '')" rows="6" />
<x-ui.input.textarea name="sms_body" label="SMS message" :value="old('sms_body', $campaign->content['sms_body'] ?? '')" rows="4" />
<p class="text-sm text-gray-500">Available customer variables: @verbatim {{customer_name}}, {{first_name}}, {{last_name}}, {{email}}, {{phone}}, {{username}} @endverbatim. Exact SMS segments appear on review.</p>
</div></x-ui.card>
<div class="flex justify-end"><x-ui.button type="submit">Save draft & review</x-ui.button></div>
</form>
@endsection
