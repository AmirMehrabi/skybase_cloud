@extends('layouts.admin')

@section('title', 'Support Teams')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-950">Support Teams</h1>
            <p class="text-sm text-slate-600">Configure ticket categories, assignment behavior, agents, and SLA targets.</p>
        </div>
        <a href="{{ route('support.teams.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#0d2f35] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#123f3d]">New team</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        @forelse($teams as $team)
            <article class="rounded-xl border border-slate-900/10 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-950">{{ $team->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $team->description }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $team->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($team->status) }}</span>
                </div>
                <dl class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Assignment</dt><dd class="font-medium text-slate-900">{{ str($team->assignment_strategy)->replace('_', ' ')->headline() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Default agent</dt><dd class="font-medium text-slate-900">{{ $team->defaultUser?->name ?? 'None' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Agents</dt><dd class="font-medium text-slate-900">{{ $team->users->count() }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Tickets</dt><dd class="font-medium text-slate-900">{{ $team->tickets_count }}</dd></div>
                </dl>
                <div class="mt-5 flex justify-end gap-2">
                    <a href="{{ route('support.teams.edit', $team) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Edit</a>
                    <form method="POST" action="{{ route('support.teams.destroy', $team) }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-slate-900/10 bg-white p-10 text-center text-slate-500 lg:col-span-3">No support teams exist yet.</div>
        @endforelse
    </div>
</div>
@endsection
