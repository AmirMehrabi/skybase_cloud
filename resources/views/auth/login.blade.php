@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="rounded-[2rem] border border-slate-950/10 bg-white p-8 shadow-xl" x-data="{
    email: '',
    password: '',
    remember: false,
    loading: false
}">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-950">Welcome Back</h2>
        <p class="mt-1 text-slate-600">Sign in to your ISP account</p>
    </div>

    <form method="POST" action="{{ route('auth.login.store') }}" @submit="loading = true">
        @csrf

        <x-input.email
            id="email"
            name="email"
            label="Email Address"
            placeholder="you@example.com"
            :required="true"
            :autofocus="true"
            icon="envelope"
            xModel="email"
        />

        <div class="space-y-2 mb-4">
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                    <i class="fas fa-lock"></i>
                </span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    x-model="password"
                    class="w-full rounded-lg border border-slate-300 bg-white py-3 pl-10 pr-12 text-slate-950 placeholder-slate-500 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-600"
                    placeholder="••••••••"
                >
                <button type="button" @click="$el.previousElementSibling.type = $el.previousElementSibling.type === 'password' ? 'text' : 'password'" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 transition hover:text-slate-700">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="remember" x-model="remember" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                <span class="ml-2 text-sm text-slate-700">Remember me</span>
            </label>
            @if(false)
            <a href="#" class="text-sm text-teal-700 transition hover:text-teal-800">Forgot password?</a>
            @endif
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            class="w-full rounded-full bg-[#f5c542] px-4 py-3 font-bold text-slate-950 transition hover:bg-[#ffd95d] focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span x-show="!loading">Sign In</span>
            <span x-show="loading" x-cloak>
                <i class="fas fa-spinner fa-spin mr-2"></i>Signing in...
            </span>
        </button>
    </form>

    @if (config('app.cloud.enabled'))
        <!-- Register Link -->
    <div class="mt-6 text-center">
        <p class="text-slate-600">
            Don't have an ISP account yet?
            <a href="{{ route('auth.register') }}" class="font-semibold text-teal-700 transition hover:text-teal-800">Create Free Account</a>
        </p>
    </div>        
    @endif

</div>
@endsection
