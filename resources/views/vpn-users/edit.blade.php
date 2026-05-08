@extends('layouts.admin')

@section('title', 'Edit VPN User')

@section('content')
<div class="space-y-6 pb-24">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit VPN User</h1>
        <p class="mt-1 text-sm text-gray-500">Update OpenVPN account settings</p>
    </div>

    <form method="POST" action="{{ route('vpn-users.update', $vpnUser) }}">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Account Details</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <x-ui.input.text label="Username" name="username" placeholder="vpn_user_01" :required="true" :value="old('username', $vpnUser->username)" :error="$errors->first('username')" />
                <div class="flex items-center pt-6">
                    <x-ui.input.checkbox label="Active" name="active" :checked="old('active', $vpnUser->active)" :error="$errors->first('active')" />
                </div>
                <x-ui.input.text type="password" label="New Password" name="password" :error="$errors->first('password')" hint="Leave blank to keep the current password." />
                <x-ui.input.text type="password" label="Confirm New Password" name="password_confirmation" />
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-gray-200 bg-white p-4 shadow-lg lg:left-64">
            <div class="flex items-center justify-between gap-3">
                <button type="submit" form="delete-vpn-user-form" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50" onclick="return confirm('Delete this VPN user?')">Delete</button>
                <div class="flex items-center gap-3">
                    <a href="{{ route('vpn-users.show', $vpnUser) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>

    <form id="delete-vpn-user-form" method="POST" action="{{ route('vpn-users.destroy', $vpnUser) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
