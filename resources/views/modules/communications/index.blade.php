@extends('layouts.admin')
@section('title', 'Templates & notification rules')
@section('content')
<x-communication-nav />
<div class="mb-6 flex items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold text-gray-900">Templates & notification rules</h1><p class="mt-1 text-sm text-gray-500">Reusable campaign content and automatic messages tied to application events.</p></div><a href="{{ route('communications.create') }}" class="rounded-lg bg-teal-900 px-4 py-2 text-sm font-semibold text-white">New template</a></div>
<x-ui.card><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b text-gray-500"><tr><th class="p-3">Template</th><th>Trigger</th><th>Audience</th><th>Channels</th><th>Status</th></tr></thead><tbody>
@forelse($templates as $template)<tr class="border-b border-gray-100"><td class="p-3"><a href="{{ route('communications.edit', $template) }}" class="font-medium text-teal-800">{{ $template->name }}</a><p class="text-xs text-gray-500">Version {{ $template->version }}</p></td><td>{{ $template->event_key ? App\Support\Notifications\NotificationEventRegistry::event($template->event_key)['label'] : 'Manual campaign' }}</td><td>{{ ucfirst($template->recipient_type) }}</td><td>{{ implode(', ', $template->channels) }}</td><td>{{ $template->enabled ? 'Enabled' : 'Disabled' }}</td></tr>
@empty<tr><td colspan="5" class="p-10 text-center text-gray-500">No templates yet. Add a campaign template or select an application event for automatic delivery.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $templates->links() }}</div></x-ui.card>
@endsection
