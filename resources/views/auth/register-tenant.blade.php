@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="rounded-[2rem] border border-slate-950/10 bg-white p-8 shadow-xl" x-data="{
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
        <h2 class="text-2xl font-bold text-slate-950">Create Your Free Account</h2>
        <p class="mt-1 text-slate-600">14-day trial. No credit card required.</p>
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
            <label for="password" class="block text-sm font-medium text-slate-700">Password <span class="text-red-600">*</span></label>
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                x-model="password"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-950 placeholder-slate-500 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-600"
                placeholder="••••••••"
            >
            <p class="text-xs text-slate-500">Must be at least 8 characters</p>
        </div>

        <div class="space-y-2 mb-4">
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password <span class="text-red-600">*</span></label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                minlength="8"
                x-model="passwordConfirmation"
                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-950 placeholder-slate-500 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-emerald-600"
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
                'SL' => 'Sierra Leone',
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
            label='I agree to the Terms of Service and Privacy Policy'
        />

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="loading"
            class="mt-6 w-full rounded-full bg-[#f5c542] px-4 py-3 font-bold text-slate-950 transition hover:bg-[#ffd95d] focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <span x-show="!loading">Create Free Account</span>
            <span x-show="loading" x-cloak>
                <i class="fas fa-spinner fa-spin mr-2"></i>Creating account...
            </span>
        </button>
    </form>

    <!-- Login Link -->
    <div class="mt-6 text-center">
        <p class="text-slate-600">
            Already have an account?
            <a href="{{ route('auth.login') }}" class="font-semibold text-teal-700 transition hover:text-teal-800">Sign in</a>
        </p>
    </div>
</div>
@endsection
