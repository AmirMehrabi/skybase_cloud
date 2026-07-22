@extends('layouts.admin')

@section('title', $ticket->ticket_number)

@php
    $statusClass = match ($ticket->status) {
        'pending_customer' => 'bg-amber-100 text-amber-800',
        'pending_staff', 'open', 'new' => 'bg-blue-100 text-blue-800',
        'resolved', 'closed' => 'bg-slate-200 text-slate-700',
        default => 'bg-slate-100 text-slate-700',
    };
    $priorityClass = match ($ticket->priority) {
        'urgent' => 'bg-red-100 text-red-700',
        'high' => 'bg-orange-100 text-orange-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

@section('content')
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
    <section class="min-w-0 space-y-5">
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500"><span>{{ $ticket->ticket_number }}</span><span class="text-slate-300">·</span><span>{{ $ticket->team?->name ?? 'Unassigned team' }}</span></div>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $ticket->subject }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-600"><span class="font-medium text-slate-900">{{ $ticket->customer?->full_name ?? 'Unknown customer' }}</span><span class="text-slate-300">·</span><span>{{ $ticket->subscription?->pppoe_username ?: ($ticket->subscription?->subscription_code ?? 'No service linked') }}</span></div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span><span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $priorityClass }}">{{ ucfirst($ticket->priority) }} priority</span><a href="{{ route('work-orders.create', ['customer_id' => $ticket->customer_id, 'ticket_id' => $ticket->id]) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Create work order</a></div>
            </div>
            <div class="mt-5 grid gap-3 border-t border-slate-100 pt-4 text-xs sm:grid-cols-3"><div><p class="text-slate-500">Last activity</p><p class="mt-1 font-semibold text-slate-900">{{ $ticket->last_activity_at?->diffForHumans() ?? 'No activity' }}</p></div><div><p class="text-slate-500">First response</p><p class="mt-1 font-semibold {{ $ticket->sla_state === 'breached' ? 'text-red-700' : 'text-slate-900' }}">{{ $ticket->first_response_due_at?->diffForHumans() ?? 'Not set' }}</p></div><div><p class="text-slate-500">Current responsibility</p><p class="mt-1 font-semibold text-slate-900">{{ $ticket->status === 'pending_customer' ? 'Waiting on customer' : ($ticket->assignedUser?->name ?? 'Team queue') }}</p></div></div>
        </div>

        <div class="space-y-3" aria-label="Ticket conversation">
            @php($lastDate = null)
            @foreach($timeline as $item)
                @php($itemDate = $item->created_at?->format('Y-m-d'))
                @if($itemDate !== $lastDate)
                    <div class="flex items-center gap-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400"><span class="h-px flex-1 bg-slate-200"></span><span>{{ $item->created_at?->format('M j, Y') }}</span><span class="h-px flex-1 bg-slate-200"></span></div>
                    @php($lastDate = $itemDate)
                @endif
                @if($item instanceof \App\Models\TicketMessage)
                    @php($isInternal = $item->visibility === 'internal')
                    @php($isCustomer = $item->author_type === 'customer')
                    <article class="rounded-2xl border p-5 shadow-sm {{ $isInternal ? 'border-amber-200 bg-amber-50/80' : ($isCustomer ? 'border-sky-200 bg-sky-50/50' : 'border-slate-900/10 bg-white') }}">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $isInternal ? 'bg-amber-200 text-amber-900' : ($isCustomer ? 'bg-sky-200 text-sky-900' : 'bg-[#0d2f35] text-white') }}">{{ strtoupper(substr($item->authorName(), 0, 2)) }}</div>
                                <div class="min-w-0"><div class="truncate text-sm font-semibold text-slate-900">{{ $item->authorName() }}</div><div class="text-xs text-slate-500">{{ $isInternal ? 'Internal note' : ($isCustomer ? 'Customer reply' : 'Staff reply') }} · {{ $item->created_at?->format('H:i') }}</div></div>
                            </div>
                            @if($isInternal)<span class="shrink-0 rounded-full bg-amber-200 px-2.5 py-1 text-xs font-semibold text-amber-900">Internal only</span>@else<span class="shrink-0 rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-900/10">{{ $isCustomer ? 'Customer' : 'Public reply' }}</span>@endif
                        </div>
                        <x-tickets.message-body :message="$item" />
                        @if($item->attachments->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-900/10 pt-3">@foreach($item->attachments as $attachment)<a href="{{ route('support.tickets.attachments.download', [$ticket, $attachment]) }}" class="inline-flex max-w-full items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"><span class="truncate">{{ $attachment->downloadName() }}</span><span class="shrink-0 text-slate-400">{{ number_format($attachment->size / 1024, 0) }} KB</span></a>@endforeach</div>
                        @endif
                    </article>
                @else
                    <div class="flex items-center gap-3 px-3 py-1 text-xs text-slate-500"><span class="h-6 w-6 rounded-full bg-slate-200"></span><span><span class="font-semibold text-slate-700">{{ $item->actorName() }}</span> {{ str($item->event_type)->replace('.', ' ')->headline() }}@if($eventDetails[$item->id] ?? null) <span class="font-medium text-slate-700">({{ $eventDetails[$item->id] }})</span>@endif</span><time class="ml-auto shrink-0">{{ $item->created_at?->format('H:i') }}</time></div>
                @endif
            @endforeach
        </div>

        <form method="POST" action="{{ route('support.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm sm:p-6" x-data="ticketReplyComposer()" @submit="syncVisibility">
            @csrf
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-semibold text-slate-950">Continue the conversation</h2><p class="text-xs text-slate-500" x-text="mode === 'internal' ? 'Only staff with access to this ticket will see this note.' : 'The customer will receive this as a public reply.'"></p></div><div class="flex rounded-lg bg-slate-100 p-1"><button type="button" @click="mode = 'public'" :class="mode === 'public' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Reply to customer</button><button type="button" @click="mode = 'internal'" :class="mode === 'internal' ? 'bg-amber-100 text-amber-900 shadow-sm' : 'text-slate-500'" class="rounded-md px-3 py-1.5 text-xs font-semibold">Internal note</button></div></div>
            <input type="hidden" name="visibility" x-model="mode">
            <x-tickets.markdown-composer id="body" name="body" label="Message" hint="Use the toolbar for links, emphasis, and readable steps." rows="6" required />
            <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><label class="block text-sm font-medium text-slate-700" for="reply-attachments">Attachments</label><input id="reply-attachments" name="attachments[]" type="file" multiple class="mt-1 block max-w-full text-xs text-slate-500"></div><button class="rounded-lg bg-[#0d2f35] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]" x-text="mode === 'internal' ? 'Add internal note' : 'Send reply'">Send reply</button></div>
        </form>
    </section>

    <aside class="space-y-4" x-data="ticketAssignment(@js((string) old('ticket_team_id', $ticket->ticket_team_id)), @js((string) old('assigned_user_id', $ticket->assigned_user_id)), @js($teamAgents))">
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 shadow-sm"><h2 class="mb-4 text-base font-semibold text-slate-950">Workflow</h2><form method="POST" action="{{ route('support.tickets.status', $ticket) }}" class="mb-3 flex gap-2">@csrf @method('PATCH')<select name="status" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach(['open', 'pending_customer', 'pending_staff', 'resolved', 'closed'] as $status)<option value="{{ $status }}" @selected($ticket->status === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>@endforeach</select><button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Save</button></form><form method="POST" action="{{ route('support.tickets.priority', $ticket) }}" class="mb-5 flex gap-2">@csrf @method('PATCH')<select name="priority" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach(['low', 'normal', 'high', 'urgent'] as $priority)<option value="{{ $priority }}" @selected($ticket->priority === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select><button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Priority</button></form><div class="border-t border-slate-100 pt-5"><h3 class="text-sm font-semibold text-slate-900">Ownership</h3><p class="mt-1 text-xs leading-5 text-slate-500">Move the ticket to a team and optionally assign a specific active agent.</p><form method="POST" action="{{ route('support.tickets.assign', $ticket) }}">@csrf @method('PATCH')<label for="ticket_team_id" class="mt-4 block text-sm font-medium text-slate-700">Team</label><select id="ticket_team_id" name="ticket_team_id" x-model="selectedTeamId" @change="selectTeam" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm">@foreach($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach</select>@error('ticket_team_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror<label for="assigned_user_id" class="mt-4 block text-sm font-medium text-slate-700">Agent</label><select id="assigned_user_id" name="assigned_user_id" x-model="selectedAssigneeId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm"><option value="">Team queue (unassigned)</option><template x-for="agent in availableAgents" :key="agent.id"><option :value="String(agent.id)" x-text="agent.name"></option></template></select><p x-show="availableAgents.length === 0" class="mt-2 text-xs text-amber-700">This team has no active agents. The ticket will remain in the team queue.</p><button class="mt-4 w-full rounded-lg bg-[#0d2f35] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#123f47]">Update ownership</button></form></div></div>
        <div class="rounded-2xl border border-slate-900/10 bg-white p-5 text-sm shadow-sm"><h2 class="mb-4 text-base font-semibold text-slate-950">Service context</h2><dl class="space-y-3"><div><dt class="text-slate-500">PPPoE username</dt><dd class="break-all font-mono font-semibold text-emerald-800">{{ $ticket->subscription?->pppoe_username ?? 'None' }}</dd></div><div><dt class="text-slate-500">Subscription</dt><dd class="font-medium text-slate-900">{{ $ticket->subscription?->subscription_code ?? 'None' }}</dd></div><div><dt class="text-slate-500">Plan</dt><dd class="font-medium text-slate-900">{{ $ticket->subscription?->plan?->name ?? 'None' }}</dd></div><div><dt class="text-slate-500">Customer</dt><dd class="font-medium text-slate-900">{{ $ticket->customer?->full_name ?? 'None' }}</dd></div><div><dt class="text-slate-500">Contact</dt><dd class="break-all font-medium text-slate-900">{{ $ticket->customer?->mobile ?: ($ticket->customer?->email ?? 'None') }}</dd></div></dl></div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    function ticketReplyComposer() {
        return { mode: '{{ old('visibility', 'public') }}', syncVisibility() {} };
    }

    function ticketAssignment(initialTeamId, initialAssigneeId, teamAgentsMap) {
        return { selectedTeamId: initialTeamId, selectedAssigneeId: initialAssigneeId, teamAgentsMap: teamAgentsMap, get availableAgents() { return this.teamAgentsMap[this.selectedTeamId] || []; }, selectTeam() { if (! this.availableAgents.some((agent) => String(agent.id) === String(this.selectedAssigneeId))) this.selectedAssigneeId = ''; } };
    }
</script>
@endpush
