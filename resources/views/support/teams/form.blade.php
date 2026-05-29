@csrf
<x-form.validation-summary />

<div class="grid gap-4 md:grid-cols-2">
    <x-input.text id="name" name="name" label="Name" :value="$team->name" required />
    <x-input.text id="slug" name="slug" label="Slug" :value="$team->slug" />
    <x-input.select id="status" name="status" label="Status" :value="$team->status" :options="['active' => 'Active', 'inactive' => 'Inactive']" required />
    <x-input.select id="assignment_strategy" name="assignment_strategy" label="Assignment strategy" :value="$team->assignment_strategy" :options="['queue' => 'Team queue', 'random' => 'Random active agent', 'default_agent' => 'Default agent']" required />
    <x-input.select id="default_user_id" name="default_user_id" label="Default agent" :value="$team->default_user_id" :options="$agents->pluck('name', 'id')" placeholder="None" />
    <x-input.text id="sort_order" name="sort_order" label="Sort order" :value="$team->sort_order ?? 0" />
    <x-input.text id="first_response_minutes" name="first_response_minutes" label="First response SLA minutes" :value="$team->first_response_minutes ?? 240" required />
    <x-input.text id="resolution_minutes" name="resolution_minutes" label="Resolution SLA minutes" :value="$team->resolution_minutes ?? 2880" required />
</div>

<x-input.textarea id="description" name="description" label="Description" :value="$team->description" rows="3" />

@php
    $selectedUsers = collect(old('users', $team->exists ? $team->users->pluck('id')->all() : []))->map(fn ($id) => (string) $id);
    $autoUsers = collect(old('auto_assign_users', $team->exists ? $team->users->filter(fn ($user) => $user->pivot->accepts_auto_assignment)->pluck('id')->all() : []))->map(fn ($id) => (string) $id);
@endphp

<div class="mb-4 rounded-xl border border-slate-900/10 p-4">
    <div class="mb-3">
        <h2 class="text-base font-semibold text-slate-950">Agents</h2>
        <p class="text-sm text-slate-500">Team members can see team tickets. Auto-assignment only uses checked auto agents.</p>
    </div>
    <div class="grid gap-3 md:grid-cols-2">
        @foreach($agents as $agent)
            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                <input type="checkbox" name="users[]" value="{{ $agent->id }}" class="mt-1" @checked($selectedUsers->contains((string) $agent->id))>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-900">{{ $agent->name }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $agent->email }}</span>
                    <span class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="auto_assign_users[]" value="{{ $agent->id }}" @checked($autoUsers->contains((string) $agent->id))>
                        Accepts auto-assignment
                    </span>
                </span>
            </label>
        @endforeach
    </div>
</div>

<div class="flex justify-end gap-3">
    <a href="{{ route('support.teams.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</a>
    <button class="rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white">Save team</button>
</div>
