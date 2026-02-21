@extends('layouts.admin')

@section('title', 'User Management')

@push('styles')
<style>
    .user-avatar {
        @apply w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
            <p class="text-gray-600 mt-1">Manage users for your ISP account</p>
        </div>
        <a href="{{ route('admin.tenant.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <i class="fas fa-plus"></i>
            <span>Add User</span>
        </a>
    </div>
</div>

<!-- Usage Alert -->
@auth()
    @php
        $tenant = auth()->user()->tenant;
        $plan = $tenant->plan;
        $userCount = \App\Models\User::where('tenant_id', $tenant->id)->count();
        $maxUsers = $plan->max_users ?? 10;
        $usagePercent = ($userCount / $maxUsers) * 100;
    @endphp

    @if($usagePercent >= 100)
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-900">User limit reached</p>
                <p class="text-sm text-red-700">You've reached your plan's limit of {{ $maxUsers }} users. Upgrade your plan to add more users.</p>
            </div>
            <a href="{{ route('plans.index') }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                Upgrade Plan
            </a>
        </div>
    @elseif($usagePercent >= 80)
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-yellow-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-yellow-900">{{ $maxUsers - $userCount }} users remaining</p>
                <p class="text-sm text-yellow-700">You're using {{ number_format($usagePercent, 0) }}% of your plan's user limit.</p>
            </div>
        </div>
    @endif
@endauth

<!-- Users Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="user-avatar">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($user->role === 'owner') bg-purple-100 text-purple-800
                        @elseif($user->role === 'admin') bg-blue-100 text-blue-800
                        @elseif($user->role === 'billing') bg-green-100 text-green-800
                        @elseif($user->role === 'support') bg-yellow-100 text-yellow-800
                        @elseif($user->role === 'noc') bg-orange-100 text-orange-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    @if($user->status === 'active')
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <span class="w-1.5 h-1.5 bg-green-600 rounded-full"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                            Inactive
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.tenant.users.edit', $user) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.tenant.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center">
                    <div class="text-gray-400">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p class="text-lg font-medium">No users found</p>
                        <p class="text-sm">Add team members to help manage your ISP operations.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination -->
    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
        </div>
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Roles Legend -->
<div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Role Permissions</h3>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-xs">
        <div>
            <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-800 font-medium mb-1">Owner</span>
            <p class="text-gray-600">Full access to all resources and settings</p>
        </div>
        <div>
            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 font-medium mb-1">Admin</span>
            <p class="text-gray-600">Manage customers, billing, routers, and users</p>
        </div>
        <div>
            <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 font-medium mb-1">Billing</span>
            <p class="text-gray-600">Manage invoices, payments, and billing</p>
        </div>
        <div>
            <span class="inline-flex items-center px-2 py-1 rounded bg-yellow-100 text-yellow-800 font-medium mb-1">Support</span>
            <p class="text-gray-600">View and manage customer support tickets</p>
        </div>
        <div>
            <span class="inline-flex items-center px-2 py-1 rounded bg-orange-100 text-orange-800 font-medium mb-1">NOC</span>
            <p class="text-gray-600">Manage routers and network operations</p>
        </div>
    </div>
</div>
@endsection
