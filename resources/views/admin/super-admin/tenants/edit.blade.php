@extends('layouts.admin')

@section('title', 'Edit Tenant')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.super-admin.tenants.show', $tenant) }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-2">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Tenant</span>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Edit Tenant: {{ $tenant->company_name }}</h1>
</div>

<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.super-admin.tenants.update', $tenant) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        @csrf
        @method('put')

        <!-- Company Information -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $tenant->name) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        value="{{ old('company_name', $tenant->company_name) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $tenant->email) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $tenant->phone) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <input
                        type="text"
                        id="country"
                        name="country"
                        value="{{ old('country', $tenant->country) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                    <select
                        id="timezone"
                        name="timezone"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="UTC" {{ $tenant->timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
                        <option value="America/New_York" {{ $tenant->timezone === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                        <option value="America/Chicago" {{ $tenant->timezone === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                        <option value="America/Denver" {{ $tenant->timezone === 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                        <option value="America/Los_Angeles" {{ $tenant->timezone === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                        <option value="Europe/London" {{ $tenant->timezone === 'Europe/London' ? 'selected' : '' }}>London (GMT)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Subscription Status -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select
                        id="status"
                        name="status"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="pending" {{ $tenant->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="active" {{ $tenant->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div>
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-2">Plan</label>
                    <select
                        id="plan_id"
                        name="plan_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">No Plan</option>
                        @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ $tenant->plan_id === $plan->id ? 'selected' : '' }}>{{ $plan->name }} (${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="trial_ends_at" class="block text-sm font-medium text-gray-700 mb-2">Trial End Date</label>
                    <input
                        type="date"
                        id="trial_ends_at"
                        name="trial_ends_at"
                        value="{{ old('trial_ends_at', $tenant->trial_ends_at?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-xs text-gray-500 mt-1">Leave empty to remove trial period</p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.super-admin.tenants.show', $tenant) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
