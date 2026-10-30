@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <div class="rounded-2xl border border-slate-950/10 bg-white p-8 shadow-xl">
        <h1 class="text-2xl font-bold text-slate-950">Check your inbox</h1>
        <p class="mt-3 leading-7 text-slate-600">We sent a verification link to your email address. Open it to confirm it’s yours and continue to your SkyBase workspace.</p>
        <p class="mt-3 text-sm leading-6 text-slate-500">If it doesn’t arrive in a few minutes, check your spam folder or send yourself another link.</p>

        @if (session('status'))
            <p class="mt-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#0d2f35] px-5 py-3 font-semibold text-white transition hover:bg-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">Send a new verification link</button>
        </form>

        <form method="POST" action="{{ route('auth.logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full px-5 py-3 text-sm font-semibold text-slate-600 transition hover:text-slate-950">Log out</button>
        </form>
    </div>
@endsection
