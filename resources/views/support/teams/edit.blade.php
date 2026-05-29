@extends('layouts.admin')

@section('title', 'Edit Support Team')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">Edit Support Team</h1>
        <p class="text-sm text-slate-600">Update {{ $team->name }} routing, agents, and SLA targets.</p>
    </div>
    <form method="POST" action="{{ route('support.teams.update', $team) }}" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
        @method('PUT')
        @include('support.teams.form')
    </form>
</div>
@endsection
