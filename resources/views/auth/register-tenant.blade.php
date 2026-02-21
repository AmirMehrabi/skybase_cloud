@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8" x-data="{
    companyName: '',
    ownerName: '',
    email: '',
    password: '',
    passwordConfirmation: '',
    phone: '',
    country: '',
    timezone: 'UTC',
    loading: false
}">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Start Your Free Trial</h2>
        <p class="text-gray-600 mt-1">14-day trial. No credit card required.</p>
    </div>

    <form method="POST" action="{{ route('auth.register.store') }}" @submit="loading = true">
        @csrf

        <!-- Company Name -->
        <div class="space-y-2 mb-4">
            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name <span class="text-red-600">*</span></label>
            <input
                type="text"
                id="company_name"
                name="company_name"
                value="{{ old('company_name') }}"
                required
                x-model="companyName"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                placeholder="Acme ISP Inc."
            >
        </div>

        <!-- Owner Name -->
        <div class="space-y-2 mb-4">
            <label for="owner_name" class="block text-sm font-medium text-gray-700">Owner Name <span class="text-red-600">*</span></label>
            <input
                type="text"
                id="owner_name"
                name="owner_name"
                value="{{ old('owner_name') }}"
                required
                x-model="ownerName"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                placeholder="John Doe"
            >
        </div>

        <!-- Email -->
        <div class="space-y-2 mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-600">*</span></label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                x-model="email"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                placeholder="john@example.com"
            >
        </div>

        <!-- Password -->
        <div class="space-y-2 mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-600">*</span></label>
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                x-model="password"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                placeholder="••••••••"
            >
            <p class="text-xs text-gray-500">Must be at least 8 characters</p>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-2 mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-600">*</span></label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                minlength="8"
                x-model="passwordConfirmation"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                placeholder="••••••••"
            >
        </div>

        <!-- Phone -->
        <div class="space-y-2 mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                value="{{ old('phone') }}"
                x-model="phone"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
                placeholder="+1 234 567 8900"
            >
        </div>

        <!-- Country -->
        <div class="space-y-2 mb-4">
            <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
            <select
                id="country"
                name="country"
                x-model="country"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            >
                <option value="">Select a country</option>
                <option value="US">United States</option>
                <option value="UK">United Kingdom</option>
                <option value="CA">Canada</option>
                <option value="AU">Australia</option>
                <option value="DE">Germany</option>
                <option value="FR">France</option>
                <option value="IN">India</option>
                <option value="BR">Brazil</option>
                <option value="NG">Nigeria</option>
                <option value="KE">Kenya</option>
                <option value="ZA">South Africa</option>
                <option value="OTHER">Other</option>
            </select>
        </div>

        <!-- Timezone -->
        <div class="space-y-2 mb-6">
            <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
            <select
                id="timezone"
                name="timezone"
                x-model="timezone"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
            >
                <option value="UTC">UTC (Coordinated Universal Time)</option>
                <option value="America/New_York">Eastern Time (US & Canada)</option>
                <option value="America/Chicago">Central Time (US & Canada)</option>
                <option value="America/Denver">Mountain Time (US & Canada)</option>
                <option value="America/Los_Angeles">Pacific Time (US & Canada)</option>
                <option value="Europe/London">London (GMT)</option>
                <option value="Europe/Paris">Central European Time</option>
                <option value="Asia/Kolkata">India Standard Time</option>
                <option value="Asia/Dubai">Gulf Standard Time</option>
                <option value="Africa/Nairobi">East Africa Time</option>
                <option value="Africa/Johannesburg">South Africa Standard Time</option>
            </select>
        </div>

        <!-- Terms -->
        <div class="mb-6">
            <label class="flex items-start gap-2">
                <input type="checkbox" required class="mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                <span class="text-sm text-gray-700">
                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-700">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-700">Privacy Policy</a>
                </span>
            </label>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span x-show="!loading">Start Free Trial</span>
            <span x-show="loading" x-cloak>
                <i class="fas fa-spinner fa-spin mr-2"></i>Creating account...
            </span>
        </button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center">
        <p class="text-gray-600">
            Already have an account?
            <a href="{{ route('auth.login') }}" class="text-blue-600 hover:text-blue-700 font-medium transition">Sign in</a>
        </p>
    </div>
</div>
@endsection
