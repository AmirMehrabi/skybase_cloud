@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8" x-data="{
    email: '',
    password: '',
    remember: false,
    loading: false
}">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Welcome Back</h2>
        <p class="text-gray-600 mt-1">Sign in to your ISP account</p>
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
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                    <i class="fas fa-lock"></i>
                </span>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    x-model="password"
                    class="w-full pl-10 pr-12 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                    placeholder="••••••••"
                >
                <button type="button" @click="$el.previousElementSibling.type = $el.previousElementSibling.type === 'password' ? 'text' : 'password'" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="remember" x-model="remember" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                <span class="ml-2 text-sm text-gray-700">Remember me</span>
            </label>
            @if(false)
            <a href="#" class="text-sm text-blue-600 hover:text-blue-700 transition">Forgot password?</a>
            @endif
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span x-show="!loading">Sign In</span>
            <span x-show="loading" x-cloak>
                <i class="fas fa-spinner fa-spin mr-2"></i>Signing in...
            </span>
        </button>
    </form>

    <!-- Register Link -->
    <div class="mt-6 text-center">
        <p class="text-gray-600">
            Don't have an ISP account yet?
            <a href="{{ route('auth.register') }}" class="text-blue-600 hover:text-blue-700 font-medium transition">Start Free Trial</a>
        </p>
    </div>
</div>
@endsection
