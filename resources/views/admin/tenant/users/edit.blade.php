@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tenant.users.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-2">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Users</span>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Edit User: {{ $user->name }}</h1>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.tenant.users.update', $user) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        @csrf
        @method('put')

        <!-- User Info -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Information</h3>
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-4">
                <p class="text-sm text-yellow-800">Leave password fields empty to keep the current password.</p>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="••••••••"
                    >
                    <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="••••••••"
                    >
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Role & Status -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Role & Status</h3>
            <div class="space-y-4">
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                    <select
                        id="role"
                        name="role"
                        required
                        @if($user->role === 'owner') disabled @endif
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        @if($user->role === 'owner')
                        <option value="owner" selected>Owner - Full account access</option>
                        @else
                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin - Full management access</option>
                        <option value="billing" {{ $user->role === 'billing' ? 'selected' : '' }}>Billing - Invoices and payments</option>
                        <option value="support" {{ $user->role === 'support' ? 'selected' : '' }}>Support - Customer support</option>
                        <option value="noc" {{ $user->role === 'noc' ? 'selected' : '' }}>NOC - Network operations</option>
                        @endif
                    </select>
                    @if($user->role === 'owner')
                    <p class="text-xs text-gray-500 mt-1">The owner role cannot be changed.</p>
                    @endif
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                    <select
                        id="status"
                        name="status"
                        required
                        @if($user->id === auth()->id()) disabled @endif
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @if($user->id === auth()->id())
                    <p class="text-xs text-gray-500 mt-1">You cannot change your own status.</p>
                    @endif
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- User Info -->
        <div class="p-4 bg-gray-50 rounded-lg mb-6">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Account Information</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Created:</span>
                    <span class="text-gray-900 ml-2">{{ $user->created_at->format('M d, Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Last Login:</span>
                    <span class="text-gray-900 ml-2">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</span>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.tenant.users.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
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
