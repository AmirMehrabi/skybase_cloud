@extends('layouts.admin')
@section('title', 'Campaigns')
@section('content')
<x-communication-nav />
<div class="mb-6 flex items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold text-gray-900">Campaigns</h1><p class="mt-1 text-sm text-gray-500">Send targeted customer announcements and follow their delivery.</p></div><a href="{{ route('campaigns.create') }}" class="rounded-lg bg-teal-900 px-4 py-2 text-sm font-semibold text-white">New campaign</a></div>
<x-ui.card>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-gray-500"><tr><th class="p-3">Campaign</th><th>Channels</th><th>Status</th><th>Scheduled</th><th>Deliveries</th></tr></thead><tbody>
@forelse($campaigns as $campaign)<tr class="border-b border-gray-100"><td class="p-3"><a class="font-medium text-teal-800" href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a></td><td>{{ implode(', ', $campaign->channels) }}</td><td>{{ str($campaign->status)->headline() }}</td><td>{{ $campaign->scheduled_at?->timezone(tenant()->timezone ?? 'UTC')->format('Y-m-d H:i') ?? 'Draft' }}</td><td>{{ $campaign->deliveries_count }}</td></tr>
@empty<tr><td colspan="5" class="p-10 text-center text-gray-500">Create your first campaign. Every send starts with a reviewable draft.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $campaigns->links() }}</div>
</x-ui.card>
@endsection
