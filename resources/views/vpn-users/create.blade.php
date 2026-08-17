@extends('layouts.admin')

@section('title', 'Add VPN User')

@section('content')
<div class="space-y-6 pb-24">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Add VPN User</h1>
        <p class="mt-1 text-sm text-gray-500">Create an OpenVPN account for this tenant</p>
    </div>

    <form method="POST" action="{{ route('vpn-users.store') }}">
        @csrf

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Account Details</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui.input.text label="Username" name="username" placeholder="vpn_user_01" :required="true" :value="old('username')" :error="$errors->first('username')" />
                @if(auth()->user()?->isOwner())<x-ui.input.select label="User Group" name="user_group_id" :options="$userGroups" :value="old('user_group_id')" placeholder="Ungrouped records only" :error="$errors->first('user_group_id')" />@endif
                <div class="flex items-center pt-6">
                    <x-ui.input.checkbox label="Active" name="active" :checked="old('active', true)" :error="$errors->first('active')" />
                </div>
                <x-ui.input.text type="password" label="Password" name="password" :required="true" :error="$errors->first('password')" />
                <x-ui.input.text type="password" label="Confirm Password" name="password_confirmation" :required="true" />
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 bg-white p-4 shadow-lg lg:left-64">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('vpn-users.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save VPN User
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
