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

        <x-input.text
            id="company_name"
            name="company_name"
            label="Company Name"
            placeholder="Acme ISP Inc."
            :required="true"
            xModel="companyName"
        />

        <x-input.text
            id="owner_name"
            name="owner_name"
            label="Owner Name"
            placeholder="John Doe"
            :required="true"
            xModel="ownerName"
        />

        <x-input.email
            id="email"
            name="email"
            label="Email Address"
            placeholder="john@example.com"
            :required="true"
            xModel="email"
        />

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

        <x-input.tel
            id="phone"
            name="phone"
            label="Phone Number"
            placeholder="+1 234 567 8900"
            xModel="phone"
        />

        <x-input.select
            id="country"
            name="country"
            label="Country"
            placeholder="Select a country"
            :options="[
                'US' => 'United States',
                'UK' => 'United Kingdom',
                'CA' => 'Canada',
                'AU' => 'Australia',
                'DE' => 'Germany',
                'FR' => 'France',
                'IN' => 'India',
                'BR' => 'Brazil',
                'NG' => 'Nigeria',
                'KE' => 'Kenya',
                'ZA' => 'South Africa',
                'OTHER' => 'Other',
            ]"
            xModel="country"
        />

        <x-input.select
            id="timezone"
            name="timezone"
            label="Timezone"
            :options="[
                'UTC' => 'UTC (Coordinated Universal Time)',
                'America/New_York' => 'Eastern Time (US & Canada)',
                'America/Chicago' => 'Central Time (US & Canada)',
                'America/Denver' => 'Mountain Time (US & Canada)',
                'America/Los_Angeles' => 'Pacific Time (US & Canada)',
                'Europe/London' => 'London (GMT)',
                'Europe/Paris' => 'Central European Time',
                'Asia/Kolkata' => 'India Standard Time',
                'Asia/Dubai' => 'Gulf Standard Time',
                'Africa/Nairobi' => 'East Africa Time',
                'Africa/Johannesburg' => 'South Africa Standard Time',
            ]"
            xModel="timezone"
        />

        <x-input.checkbox
            id="terms"
            name="terms"
            :value="true"
            :required="true"
            label='I agree to the <a href="#" class="text-blue-600 hover:text-blue-700">Terms of Service</a> and <a href="#" class="text-blue-600 hover:text-blue-700">Privacy Policy</a>'
        />

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            class="w-full py-3 px-4 mt-6 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
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
