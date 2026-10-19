@extends('layouts.admin')
@section('title', 'Notification incidents')
@section('content')
<x-communication-nav /><h1 class="mb-2 text-2xl font-semibold text-gray-900">Notification incidents</h1><p class="mb-6 text-sm text-gray-500">Confirm router outages, escalate unresolved incidents and notify contacts when service recovers.</p>
<x-ui.card title="Outage policy" subtitle="Configure enabled Router offline and Router recovered templates with incident contacts. Times use your tenant timezone.">
<form method="POST" action="{{ route('communications.policy') }}" class="space-y-5">@csrf @method('PUT')
<x-ui.input.checkbox name="enabled" label="Enable incident alerts" :checked="old('enabled', $policy['enabled'])" />
<div class="grid gap-5 md:grid-cols-3">
<x-ui.input.text type="number" name="failure_threshold" label="Consecutive failures to open" :value="old('failure_threshold', $policy['failure_threshold'])" required />
<x-ui.input.text type="number" name="recovery_threshold" label="Consecutive successes to recover" :value="old('recovery_threshold', $policy['recovery_threshold'])" required />
<x-ui.input.text name="escalation_minutes" label="Escalate after (minutes)" :value="old('escalation_minutes', implode(', ', $policy['escalation_minutes']))" hint="From incident opening, e.g. 30, 120. Maximum five stages." />
<x-ui.input.text type="time" name="quiet_start" label="Quiet hours start" :value="old('quiet_start', $policy['quiet_start'])" />
<x-ui.input.text type="time" name="quiet_end" label="Quiet hours end" :value="old('quiet_end', $policy['quiet_end'])" />
<x-ui.input.text type="datetime-local" name="maintenance_until" label="Maintenance until (optional)" :value="old('maintenance_until', $policy['maintenance_until'] ? Carbon\Carbon::parse($policy['maintenance_until'])->timezone(tenant()->timezone ?? 'UTC')->format('Y-m-d\TH:i') : '')" />
</div><x-ui.button type="submit">Save policy</x-ui.button></form></x-ui.card>
<div class="mt-6"><x-ui.card title="Incident history"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-gray-500"><tr><th class="p-3">Router</th><th>Status</th><th>Opened / resolved</th><th>Stage</th><th>Actions</th></tr></thead><tbody>
@forelse($incidents as $incident)<tr class="border-b border-gray-100"><td class="p-3">{{ $incident->router?->name }}</td><td>{{ $incident->status }}{{ $incident->acknowledged_at ? ' · acknowledged' : '' }}</td><td>{{ $incident->opened_at?->format('Y-m-d H:i') }}<br>{{ $incident->resolved_at?->format('Y-m-d H:i') }}</td><td>{{ $incident->stage }}</td><td class="flex flex-wrap gap-2 py-3">@if($incident->status==='open')@foreach(['acknowledge'=>'Acknowledge','resolve'=>'Resolve'] as $action=>$label)<form method="POST" action="{{ route('communications.incident-action', $incident) }}">@csrf<x-ui.input.hidden name="action" :value="$action" /><x-ui.button type="submit" variant="secondary" size="sm">{{ $label }}</x-ui.button></form>@endforeach<a href="{{ route('campaigns.create', ['router' => $incident->router_id]) }}" class="px-3 py-2 text-teal-800 underline">Notify affected customers</a>@endif</td></tr>
@empty<tr><td colspan="5" class="p-10 text-center text-gray-500">No confirmed incidents.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $incidents->links() }}</div></x-ui.card></div>
@endsection
