@extends('layouts.admin')
@section('title', $campaign->name)
@section('content')
<x-communication-nav :campaign="$campaign" />
<x-ui.page-header :title="$campaign->name" :description="str($campaign->status)->headline().' · '.implode(' + ', $campaign->channels).' · '.($campaign->audience_mode === 'snapshot' ? 'Fixed audience' : 'Audience evaluated at sending')" icon="megaphone" eyebrow="Campaigns">
    @if($campaign->status === 'draft' && auth()->user()->canAccessRoute('campaigns.edit'))<a class="rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50" href="{{ route('campaigns.edit', $campaign) }}">Edit draft</a>@endif
</x-ui.page-header>
@if($preview)
<div class="mb-6 grid gap-4 sm:grid-cols-3">@foreach(['selected'=>'Selected customers','eligible'=>'Eligible channel deliveries','skipped'=>'Excluded channel deliveries'] as $key=>$label)<x-ui.card><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold">{{ $preview[$key] }}</p></x-ui.card>@endforeach</div>
<p class="mb-5 text-sm text-gray-600">Exclusions include invalid or missing contact details and disabled recipient or tenant preferences. Duplicate destinations are suppressed during sending. Dynamic audiences can change before sending.</p>
<div class="mb-6 grid gap-4 lg:grid-cols-3">@foreach($preview['samples'] as $sample)<x-ui.card :title="$sample['name']"><p class="font-medium">{{ $sample['content']['subject'] }}</p><p class="mt-3 whitespace-pre-wrap text-sm">{{ $sample['content']['email_body'] }}</p><div class="mt-4 rounded-lg bg-gray-50 p-3"><p class="whitespace-pre-wrap text-sm">{{ $sample['content']['sms_body'] }}</p><p class="mt-2 text-xs text-gray-500">{{ $sample['sms']['segments'] }} SMS segments · {{ $sample['sms']['encoding'] }}</p></div></x-ui.card>@endforeach</div>
@endif
<x-ui.card title="Campaign controls" subtitle="Times use your tenant timezone. Pausing or cancelling stops remaining work; messages already being sent may finish.">
<div class="flex flex-wrap items-end gap-3">
@if($campaign->status === 'draft')
<form method="POST" action="{{ route('campaigns.action', $campaign) }}">@csrf<x-ui.input.hidden name="action" value="send" /><x-ui.button type="submit" variant="brand">Send now</x-ui.button></form>
<form method="POST" action="{{ route('campaigns.action', $campaign) }}" class="flex flex-wrap items-end gap-3">@csrf<x-ui.input.hidden name="action" value="schedule" /><x-ui.input.text type="datetime-local" name="scheduled_at" :label="'Schedule ('.(tenant()->timezone ?? 'UTC').')'" required /><x-ui.button type="submit" variant="secondary">Schedule</x-ui.button></form>
@endif
@php($actions = match($campaign->status) { 'scheduled','sending' => ['pause'=>'Pause','cancel'=>'Cancel'], 'paused' => ['resume'=>'Resume','cancel'=>'Cancel'], 'completed','completed_with_failures' => ['retry'=>'Retry failed deliveries'], default => [] })
@foreach($actions + ['duplicate'=>'Duplicate as draft'] as $action=>$label)<form method="POST" action="{{ route('campaigns.action', $campaign) }}">@csrf<x-ui.input.hidden name="action" :value="$action" /><x-ui.button type="submit" variant="secondary">{{ $label }}</x-ui.button></form>@endforeach
</div></x-ui.card>
<div class="my-6 flex flex-wrap gap-3">@foreach($stats as $status=>$total)<span class="rounded-lg bg-white px-4 py-2 text-sm shadow-sm">{{ str($status)->headline() }}: <strong>{{ $total }}</strong></span>@endforeach</div>
<x-ui.card title="Delivery results"><p class="mb-4 text-sm text-gray-500">Accepted means the gateway accepted the message. It does not confirm receipt. Unknown outcomes need provider verification before another send.</p>@include('modules.communications.delivery-table')</x-ui.card>
@endsection
