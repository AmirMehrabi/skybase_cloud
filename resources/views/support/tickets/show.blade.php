@extends('layouts.admin')

@section('title', $ticket->ticket_number)

@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <section class="space-y-6">
        <div class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-500">{{ $ticket->ticket_number }}</div>
                    <h1 class="mt-1 text-2xl font-bold text-slate-950">{{ $ticket->subject }}</h1>
                    <p class="mt-2 text-sm text-slate-600">{{ $ticket->customer?->full_name }} · {{ $ticket->customer?->email }}</p>
                </div>
                <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ str($ticket->status)->replace('_', ' ')->headline() }}</span>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($timeline as $item)
                @if($item instanceof \App\Models\TicketMessage)
                    <article class="rounded-xl border {{ $item->visibility === 'internal' ? 'border-amber-200 bg-amber-50' : 'border-slate-900/10 bg-white' }} p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $item->authorName() }}</div>
                                <div class="text-xs text-slate-500">{{ $item->created_at?->format('Y-m-d H:i') }}</div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->visibility === 'internal' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">{{ ucfirst($item->visibility) }}</span>
                        </div>
                        <x-tickets.message-body :message="$item" />
                        @if($item->attachments->isNotEmpty())
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($item->attachments as $attachment)
                                    <a href="{{ route('support.tickets.attachments.download', [$ticket, $attachment]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $attachment->downloadName() }}</a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @else
                    <div class="rounded-lg border border-slate-900/10 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                        <span class="font-medium text-slate-700">{{ $item->actorName() }}</span> · {{ str($item->event_type)->replace('.', ' ')->headline() }}@if($eventDetails[$item->id] ?? null) <span class="font-medium text-slate-700">{{ $eventDetails[$item->id] }}</span>@endif · {{ $item->created_at?->format('Y-m-d H:i') }}
                    </div>
                @endif
            @endforeach
        </div>

        <form method="POST" action="{{ route('support.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="mb-4 text-lg font-semibold text-slate-950">Add response</h2>
            <x-input.select id="visibility" name="visibility" label="Visibility" :options="['public' => 'Public reply', 'internal' => 'Internal note']" :value="old('visibility', 'public')" required />
            <x-tickets.markdown-composer id="body" name="body" label="Message" rows="6" required />
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700" for="attachments">Attachments</label>
                <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Post response</button>
        </form>
    </section>

    <aside class="space-y-4" x-data="ticketWorkflow(@js($currentTeamId), {!! $teamAgentsJson !!})">
        <div class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-950">Workflow</h2>
            <form method="POST" action="{{ route('support.tickets.status', $ticket) }}" class="mb-3 flex gap-2">
                @csrf
                @method('PATCH')
                <select name="status" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['open', 'pending_customer', 'pending_staff', 'resolved', 'closed'] as $status)
                        <option value="{{ $status }}" @selected($ticket->status === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Save</button>
            </form>
            <form method="POST" action="{{ route('support.tickets.priority', $ticket) }}" class="mb-3 flex gap-2">
                @csrf
                @method('PATCH')
                <select name="priority" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach(['low', 'normal', 'high', 'urgent'] as $priority)
                        <option value="{{ $priority }}" @selected($ticket->priority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Priority</button>
            </form>
            <form method="POST" action="{{ route('support.tickets.assign', $ticket) }}" class="mb-3 flex gap-2">
                @csrf
                @method('PATCH')
                <select name="assigned_user_id" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Queue</option>
                    <template x-for="agent in teamAgents" :key="agent.id">
                        <option :value="agent.id" x-text="agent.name" :selected="agent.id == '{{ $ticket->assigned_user_id }}'"></option>
                    </template>
                </select>
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Assign</button>
            </form>
            <form method="POST" action="{{ route('support.tickets.team', $ticket) }}" class="flex gap-2">
                @csrf
                @method('PATCH')
                <select name="ticket_team_id" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" x-model="selectedTeamId" @change="selectedAssigneeId = ''">
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" @selected($ticket->ticket_team_id === $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Move</button>
            </form>
        </div>

        <div class="rounded-xl border border-slate-900/10 bg-white p-5 text-sm shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-950">Details</h2>
            <dl class="space-y-3">
                <div><dt class="text-slate-500">Team</dt><dd class="font-medium text-slate-900">{{ $ticket->team?->name }}</dd></div>
                <div><dt class="text-slate-500">Assignee</dt><dd class="font-medium text-slate-900">{{ $ticket->assignedUser?->name ?? 'Queue' }}</dd></div>
                <div><dt class="text-slate-500">Priority</dt><dd class="font-medium text-slate-900">{{ ucfirst($ticket->priority) }}</dd></div>
                <div><dt class="text-slate-500">Service</dt><dd class="font-medium text-slate-900">{{ $ticket->subscription?->subscription_code ?? 'None' }}</dd></div>
                <div><dt class="text-slate-500">First response due</dt><dd class="font-medium text-slate-900">{{ $ticket->first_response_due_at?->format('Y-m-d H:i') ?? 'None' }}</dd></div>
                <div><dt class="text-slate-500">Resolution due</dt><dd class="font-medium text-slate-900">{{ $ticket->resolution_due_at?->format('Y-m-d H:i') ?? 'None' }}</dd></div>
            </dl>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    function ticketWorkflow(initialTeamId, teamAgentsMap) {
        return {
            selectedTeamId: initialTeamId,
            selectedAssigneeId: '',
            teamAgentsMap: teamAgentsMap,
            get teamAgents() {
                return this.teamAgentsMap[this.selectedTeamId] || [];
            },
        };
    }
</script>
@endpush
