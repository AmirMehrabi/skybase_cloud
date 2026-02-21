@extends('layouts.admin')

@section('title', 'Add User')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tenant.users.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-2">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Users</span>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Add New User</h1>
    <p class="text-gray-600 mt-1">Add a team member to your ISP account</p>
</div>

<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.tenant.users.store') }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        @csrf

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
                        value="{{ old('name') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="John Doe"
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
                        value="{{ old('email') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="john@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Password</h3>
            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="8"
                            class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="••••••••"
                        >
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
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

        <!-- Role Selection -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Role & Permissions</h3>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                <select
                    id="role"
                    name="role"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Select a role</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin - Full management access</option>
                    <option value="billing" {{ old('role') == 'billing' ? 'selected' : '' }}>Billing - Invoices and payments</option>
                    <option value="support" {{ old('role') == 'support' ? 'selected' : '' }}>Support - Customer support</option>
                    <option value="noc" {{ old('role') == 'noc' ? 'selected' : '' }}>NOC - Network operations</option>
                </select>
                @error('role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role Descriptions -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-900 mb-2">Role Permissions:</p>
                <div class="space-y-2 text-xs text-gray-600">
                    <div><strong>Admin:</strong> Full access to customers, billing, routers, reports, and user management</div>
                    <div><strong>Billing:</strong> View customers, manage invoices, payments, and billing reports</div>
                    <div><strong>Support:</strong> View and edit customers, manage routers, and handle support tickets</div>
                    <div><strong>NOC:</strong> Full router access, network monitoring, and network reports</div>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Status</h3>
            <div class="flex items-center gap-4">
                <label class="flex items-center">
                    <input type="radio" name="status" value="active" checked class="w-4 h-4 text-blue-600">
                    <span class="ml-2 text-sm text-gray-700">Active - User can login</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="status" value="inactive" class="w-4 h-4 text-blue-600">
                    <span class="ml-2 text-sm text-gray-700">Inactive - User cannot login</span>
                </label>
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
                Create User
            </button>
        </div>
    </form>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-toggle-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endsection
