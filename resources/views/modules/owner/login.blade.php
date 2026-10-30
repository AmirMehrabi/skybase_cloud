@extends('layouts.console')
@section('title', 'Console sign in')
@section('content')
<div class="mx-auto mt-8 max-w-md rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
<p class="mb-3 text-xs font-bold uppercase tracking-widest text-teal-700">SkyBase administration</p>
<h1 class="text-3xl font-bold tracking-tight">Sign in to your console</h1>
<p class="mt-3 text-sm leading-6 text-slate-600">Manage ISP accounts, commercial terms and platform access. An administrator account and multi-factor verification are required.</p>
<form method="POST" action="{{ route('auth.login.store') }}" class="mt-7 space-y-5">@csrf
<x-ui.input.text name="email" type="email" label="Administrator email" :value="old('email')" autocomplete="username" required autofocus />
<x-ui.input.text name="password" type="password" label="Password" autocomplete="current-password" required />
<button class="w-full rounded-xl bg-[#0d2f35] px-5 py-3 font-semibold text-white transition hover:bg-[#123f3d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700">Continue securely</button>
</form><p class="mt-5 text-xs leading-5 text-slate-500">Tenant staff and customer accounts sign in through their usual portals.</p>
</div>
@endsection
