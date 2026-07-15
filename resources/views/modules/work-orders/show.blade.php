@extends('layouts.admin')

@section('title', $workOrder->work_order_number)

@section('content')
<div class="space-y-6" x-data="{ panel: 'checklist' }">
    <header class="border-b border-slate-200 pb-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="font-bold text-[#0d2f35]">{{ $workOrder->work_order_number }}</span>
                    <span>·</span><span>{{ str($workOrder->type->value)->replace('_', ' ')->headline() }}</span>
                    @if($workOrder->sourceTicket)<span>·</span><a class="font-semibold text-[#0d2f35]" href="{{ route('support.tickets.show', $workOrder->sourceTicket) }}">{{ $workOrder->sourceTicket->ticket_number }}</a>@endif
                </div>
                <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $workOrder->title }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $workOrder->description ?: 'No additional scope notes.' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">{{ str($workOrder->status->value)->replace('_', ' ')->headline() }}</span>
                @can('update', $workOrder)
                    @if($workOrder->status === App\Enums\WorkOrderStatus::Draft)<a href="{{ route('work-orders.edit', $workOrder) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Edit draft</a>@endif
                @endcan
                @can('delete', $workOrder)
                    <form method="POST" action="{{ route('work-orders.destroy', $workOrder) }}" onsubmit="return confirm('Delete this work order?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Delete</button>
                    </form>
                @endcan
            </div>
        </div>
    </header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <main class="min-w-0 space-y-6">
            <div class="flex gap-1 overflow-x-auto border-b border-slate-200">
                @foreach(['checklist' => 'Checklist', 'activity' => 'Activity', 'appointments' => 'Appointments', 'materials' => 'Materials', 'evidence' => 'Evidence'] as $key => $label)
                    <button @click="panel = '{{ $key }}'" :class="panel === '{{ $key }}' ? 'border-[#0d2f35] text-[#0d2f35]' : 'border-transparent text-slate-500'" class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold">{{ $label }}</button>
                @endforeach
            </div>

            <section x-show="panel === 'checklist'" class="space-y-3">
                @forelse($workOrder->tasks as $task)
                    <form method="POST" action="{{ route('work-orders.tasks.update', [$workOrder, $task]) }}" class="grid gap-3 border border-slate-900/10 bg-white p-4 shadow-sm sm:grid-cols-[1fr_10rem_auto] sm:items-center">
                        @csrf @method('PATCH')
                        <div><p class="font-semibold text-slate-900">{{ $task->title }} @if($task->is_required)<span class="text-red-500">*</span>@endif</p><p class="text-xs text-slate-500">{{ $task->result }}</p></div>
                        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="pending" @selected($task->status === 'pending')>Pending</option><option value="completed" @selected($task->status === 'completed')>Completed</option><option value="skipped" @selected($task->status === 'skipped')>Skipped</option></select>
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Save</button>
                    </form>
                @empty
                    <p class="border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No checklist items.</p>
                @endforelse
            </section>

            <section x-show="panel === 'activity'" x-cloak class="space-y-4">
                <form method="POST" action="{{ route('work-orders.notes.store', $workOrder) }}" class="border border-slate-900/10 bg-white p-5 shadow-sm">
                    @csrf
                    <label for="body" class="text-sm font-semibold text-slate-700">Internal operational note</label>
                    <textarea id="body" name="body" rows="3" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    <div class="mt-3 text-right"><button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Add note</button></div>
                </form>
                @foreach($workOrder->events->concat($workOrder->notes)->sortByDesc('created_at') as $item)
                    <article class="border-l-2 border-slate-300 pl-4">
                        @if($item instanceof App\Models\WorkOrderEvent)
                            <p class="text-sm font-semibold text-slate-900">{{ str($item->event_type)->after('work_order.')->replace('_', ' ')->headline() }}</p>
                            <p class="text-xs text-slate-500">{{ $item->actor?->name ?? 'System' }} · {{ $item->created_at->format('M d, Y H:i') }}</p>
                        @else
                            <p class="whitespace-pre-line text-sm text-slate-700">{{ $item->body }}</p><p class="mt-1 text-xs text-slate-500">{{ $item->user?->name }} · {{ $item->created_at->format('M d, Y H:i') }}</p>
                        @endif
                    </article>
                @endforeach
            </section>

            <section x-show="panel === 'appointments'" x-cloak class="space-y-4">
                @foreach($workOrder->appointments->sortByDesc('starts_at') as $appointment)
                    <div class="border border-slate-900/10 bg-white p-4"><div class="flex justify-between gap-4"><div><p class="font-semibold">{{ $appointment->starts_at->format('M d, Y H:i') }}–{{ $appointment->ends_at->format('H:i') }}</p><p class="mt-1 text-sm text-slate-500">{{ $appointment->notes }}</p></div><span class="text-xs font-semibold uppercase text-slate-500">{{ $appointment->status }}</span></div></div>
                @endforeach
            </section>

            <section x-show="panel === 'materials'" x-cloak class="space-y-4">
                @can('execute', $workOrder)
                <form method="POST" action="{{ route('work-orders.materials.store', $workOrder) }}" class="grid gap-3 border border-slate-900/10 bg-white p-5 md:grid-cols-6">
                    @csrf
                    <select name="direction" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="installed">Installed</option><option value="issued">Issued</option><option value="removed">Removed</option><option value="returned">Returned</option></select>
                    <input name="description" required placeholder="Equipment or material" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                    <input name="serial_number" placeholder="Serial number" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input name="quantity" type="number" step="0.01" min="0.01" value="1" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Record</button>
                </form>
                @endcan
                <div class="overflow-hidden border border-slate-900/10 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Movement</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Serial</th><th class="px-4 py-3">Quantity</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">@forelse($workOrder->materials as $material)<tr><td class="px-4 py-3 font-semibold">{{ ucfirst($material->direction) }}</td><td class="px-4 py-3">{{ $material->description }}</td><td class="px-4 py-3">{{ $material->serial_number ?: '—' }}</td><td class="px-4 py-3">{{ $material->quantity }}</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No material movement recorded.</td></tr>@endforelse</tbody>
                    </table>
                </div>
            </section>

            <section x-show="panel === 'evidence'" x-cloak class="space-y-4">
                <form method="POST" action="{{ route('work-orders.attachments.store', $workOrder) }}" enctype="multipart/form-data" class="grid gap-3 border border-slate-900/10 bg-white p-5 sm:grid-cols-[10rem_1fr_auto]">
                    @csrf
                    <select name="category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach(['evidence','before','after','survey','acceptance','document'] as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select>
                    <input type="file" name="attachment" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Upload</button>
                </form>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($workOrder->attachments as $attachment)
                        <a href="{{ route('work-orders.attachments.download', [$workOrder, $attachment]) }}" class="border border-slate-200 bg-white p-4 hover:border-[#0d2f35]"><p class="truncate font-semibold text-slate-900">{{ $attachment->original_name }}</p><p class="mt-1 text-xs uppercase text-slate-500">{{ $attachment->category }}</p></a>
                    @endforeach
                </div>
            </section>
        </main>

        <aside class="space-y-4">
            <section class="border border-slate-900/10 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-950">Customer and service</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-xs uppercase text-slate-500">Customer</dt><dd class="mt-1 font-semibold">{{ $workOrder->customer?->full_name }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Service</dt><dd class="mt-1">{{ $workOrder->subscription?->pppoe_username ?: 'Not provisioned' }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Address</dt><dd class="mt-1">{{ $workOrder->service_address_line1 }}, {{ $workOrder->service_city }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Contact</dt><dd class="mt-1">{{ $workOrder->contact_name }} · {{ $workOrder->contact_phone }}</dd></div>
                </dl>
            </section>

            @can('assign', $workOrder)
            <form method="POST" action="{{ route('work-orders.assign', $workOrder) }}" class="border border-slate-900/10 bg-white p-5 shadow-sm" x-data="{ team: '{{ old('assigned_team_id', $workOrder->assigned_team_id) }}' }">
                @csrf @method('PATCH')
                <h2 class="font-bold text-slate-950">Ownership</h2>
                <label class="mt-4 block text-xs font-semibold uppercase text-slate-500">Team</label>
                <select name="assigned_team_id" x-model="team" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Select team</option>@foreach($teams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach</select>
                <label class="mt-3 block text-xs font-semibold uppercase text-slate-500">Technician</label>
                <select name="assigned_user_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Team queue</option>@foreach($teams as $team)@foreach($team->users as $user)<option x-show="team == '{{ $team->id }}'" value="{{ $user->id }}" @selected($workOrder->assigned_user_id === $user->id)>{{ $user->name }}</option>@endforeach @endforeach</select>
                <button class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Update ownership</button>
            </form>
            @endcan

            @can('schedule', $workOrder)
            <form method="POST" action="{{ route('work-orders.schedule', $workOrder) }}" class="border border-slate-900/10 bg-white p-5 shadow-sm">
                @csrf
                <h2 class="font-bold text-slate-950">Appointment</h2>
                <input type="datetime-local" name="starts_at" required class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input type="datetime-local" name="ends_at" required class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @if($workOrder->appointments->isNotEmpty())<textarea name="reschedule_reason" required placeholder="Reason for rescheduling" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>@endif
                <button class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">Schedule visit</button>
            </form>
            @endcan

            @can('execute', $workOrder)
            <form method="POST" action="{{ route('work-orders.transition', $workOrder) }}" class="border border-slate-900/10 bg-white p-5 shadow-sm">
                @csrf @method('PATCH')
                <h2 class="font-bold text-slate-950">Move workflow</h2>
                <select name="status" required class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach($workOrder->status->allowedTransitions() as $status)<option value="{{ $status }}">{{ str($status)->replace('_', ' ')->headline() }}</option>@endforeach</select>
                <textarea name="blocked_reason" placeholder="Blocked reason (when blocking)" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                <textarea name="completion_notes" placeholder="Completion notes (when completing)" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                <textarea name="cancellation_reason" placeholder="Cancellation reason (when cancelling)" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                <button class="mt-4 w-full rounded-lg bg-slate-950 px-3 py-2 text-sm font-semibold text-white">Apply transition</button>
            </form>
            @endcan

            @can('provision', $workOrder)
            @if($workOrder->status === App\Enums\WorkOrderStatus::ReadyForActivation && !$workOrder->subscription_id)
            <form method="POST" action="{{ route('work-orders.provision', $workOrder) }}" class="border-2 border-emerald-600 bg-emerald-50 p-5">
                @csrf
                <h2 class="font-bold text-emerald-950">Provision service</h2>
                <input name="name" value="{{ $workOrder->customer?->full_name }} Service" required class="mt-4 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm">
                <input type="hidden" name="service_type" value="pppoe"><input type="hidden" name="ip_management" value="router">
                <select name="plan_id" required class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"><option value="">Plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select>
                <select name="router_id" required class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"><option value="">Router</option>@foreach($routers as $router)<option value="{{ $router->id }}">{{ $router->name }}</option>@endforeach</select>
                <select name="connection_type" required class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"><option value="pppoe">PPPoE</option><option value="dhcp">DHCP</option><option value="static">Static</option></select>
                <input name="pppoe_username" placeholder="PPPoE username" class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm">
                <input name="pppoe_password" placeholder="PPPoE password" class="mt-3 w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm">
                <button class="mt-4 w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Provision subscription</button>
            </form>
            @endif
            @endcan
        </aside>
    </div>
</div>
@endsection
