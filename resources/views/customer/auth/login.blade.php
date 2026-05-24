@extends('layouts.auth')

@section('title', 'Customer Login')

@section('content')
<div class="rounded-2xl border border-slate-900/10 bg-white p-6 shadow-xl shadow-slate-900/5">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-950">Customer portal</h1>
        <p class="mt-1 text-sm text-slate-500">Sign in with your ISP tenant code and account email.</p>
    </div>

    <x-form.validation-summary :errors="$errors" />

    <form method="POST" action="{{ route('customer.login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="tenant" class="mb-1 block text-sm font-medium text-slate-700">Tenant code</label>
            <input id="tenant" name="tenant" type="text" value="{{ old('tenant') }}" required autofocus class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#0d2f35] focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            @error('tenant')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#0d2f35] focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input id="password" name="password" type="password" required class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#0d2f35] focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-[#0d2f35] focus:ring-emerald-500/30">
            Remember me
        </label>

        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-[#0d2f35] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#123f3d]">
            Sign in
        </button>
    </form>
</div>
@endsection
