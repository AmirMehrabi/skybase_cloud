@extends('layouts.admin')

@section('title', 'New Support Team')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-950">New Support Team</h1>
        <p class="text-sm text-slate-600">Create a ticket category and route it to the right agents.</p>
    </div>
    <form method="POST" action="{{ route('support.teams.store') }}" class="rounded-xl border border-slate-900/10 bg-white p-6 shadow-sm">
        @include('support.teams.form')
    </form>
</div>
@endsection
