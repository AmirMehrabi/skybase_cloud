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
            <p class="text-gray-600 mt-1">Manage team members for your ISP account</p>
        </div>
        <a href="{{ route('admin.tenant.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <i class="fas fa-plus"></i>
            <span>Add User</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <form method="GET" action="{{ route('admin.tenant.users.index') }}" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-64">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="billing" {{ request('role') === 'billing' ? 'selected' : '' }}>Billing</option>
                <option value="support" {{ request('role') === 'support' ? 'selected' : '' }}>Support</option>
                <option value="noc" {{ request('role') === 'noc' ? 'selected' : '' }}>NOC</option>
            </select>
        </div>
        <div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
        @if(request()->hasAny(['search', 'role', 'status']))
        <a href="{{ route('admin.tenant.users.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
            Clear
        </a>
        @endif
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
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
                                @if($user->id === auth()->id())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                    You
                                </span>
                                @endif
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
                            {{ $user->getRoleDisplayName() }}
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
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $user->created_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.tenant.users.show', $user) }}" class="text-gray-600 hover:text-gray-900" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.tenant.users.edit', $user) }}" class="text-gray-600 hover:text-gray-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id !== auth()->id() && $user->role !== 'owner')
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
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p class="text-lg font-medium">No users found</p>
                            <p class="text-sm">@if(request()->hasAny(['search', 'role', 'status'])) Try adjusting your filters @else Add team members to help manage your ISP operations @endif</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

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
