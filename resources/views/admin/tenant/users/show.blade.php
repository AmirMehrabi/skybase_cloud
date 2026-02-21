@extends('layouts.admin')

@section('title', "User: {$user->name}")

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tenant.users.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-2">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Users</span>
    </a>
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $user->email }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.tenant.users.edit', $user) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <i class="fas fa-edit"></i>
                <span>Edit User</span>
            </a>
        </div>
    </div>
</div>

<!-- User Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Profile Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
            <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
            <p class="text-gray-500">{{ $user->email }}</p>

            <div class="mt-4 space-y-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @if($user->role === 'owner') bg-purple-100 text-purple-800
                    @elseif($user->role === 'admin') bg-blue-100 text-blue-800
                    @elseif($user->role === 'billing') bg-green-100 text-green-800
                    @elseif($user->role === 'support') bg-yellow-100 text-yellow-800
                    @elseif($user->role === 'noc') bg-orange-100 text-orange-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ $user->getRoleDisplayName() }}
                </span>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium
                    @if($user->status === 'active') bg-green-100 text-green-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    @if($user->status === 'active')
                    <span class="w-1.5 h-1.5 bg-green-600 rounded-full"></span>
                    @endif
                    {{ ucfirst($user->status) }}
                </span>
            </div>
        </div>

        <hr class="my-4">

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">User ID:</span>
                <span class="text-gray-900">#{{ $user->id }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tenant ID:</span>
                <span class="text-gray-900 font-mono text-xs">{{ substr($user->tenant_id, 0, 8) }}...</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Created:</span>
                <span class="text-gray-900">{{ $user->created_at->format('M d, Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Last Login:</span>
                <span class="text-gray-900">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</span>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>

        @if($recentActivity->count() > 0)
        <div class="space-y-4">
            @foreach($recentActivity as $activity)
            <div class="flex items-start gap-4 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                <div class="w-10 h-10 rounded-full
                    @if($activity->action === 'user.created') bg-green-100
                    @elseif($activity->action === 'user.updated') bg-blue-100
                    @elseif($activity->action === 'user.deleted') bg-red-100
                    @else bg-gray-100
                    @endif flex items-center justify-center flex-shrink-0">
                    <i class="fas
                        @if($activity->action === 'user.created') fa-user-plus text-green-600
                        @elseif($activity->action === 'user.updated') fa-edit text-blue-600
                        @elseif($activity->action === 'user.deleted') fa-trash text-red-600
                        @else fa-circle text-gray-600
                        @endif"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        @if($activity->action === 'user.created')
                            User created
                        @elseif($activity->action === 'user.updated')
                            User updated
                        @elseif($activity->action === 'user.deleted')
                            User deleted
                        @else
                            Activity logged
                        @endif
                    </p>
                    @if($activity->ip_address)
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $activity->ip_address }}
                    </p>
                    @endif
                    <p class="text-xs text-gray-400 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8 text-gray-400">
            <i class="fas fa-history text-3xl mb-2"></i>
            <p>No recent activity</p>
        </div>
        @endif
    </div>
</div>

<!-- Role Permissions -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Role Permissions</h3>
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 text-sm">
        <div class="@if($user->role === 'owner') bg-purple-50 border-purple-200 @else bg-gray-50 border-gray-200 @endif border rounded-lg p-4">
            <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-800 font-medium mb-2">Owner</span>
            <p class="text-gray-600 @if($user->role === 'owner') font-medium @endif">Full access to all resources and settings</p>
        </div>
        <div class="@if($user->role === 'admin') bg-blue-50 border-blue-200 @else bg-gray-50 border-gray-200 @endif border rounded-lg p-4">
            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 font-medium mb-2">Admin</span>
            <p class="text-gray-600 @if($user->role === 'admin') font-medium @endif">Manage customers, billing, routers, and users</p>
        </div>
        <div class="@if($user->role === 'billing') bg-green-50 border-green-200 @else bg-gray-50 border-gray-200 @endif border rounded-lg p-4">
            <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 font-medium mb-2">Billing</span>
            <p class="text-gray-600 @if($user->role === 'billing') font-medium @endif">Manage invoices, payments, and billing</p>
        </div>
        <div class="@if($user->role === 'support') bg-yellow-50 border-yellow-200 @else bg-gray-50 border-gray-200 @endif border rounded-lg p-4">
            <span class="inline-flex items-center px-2 py-1 rounded bg-yellow-100 text-yellow-800 font-medium mb-2">Support</span>
            <p class="text-gray-600 @if($user->role === 'support') font-medium @endif">View and manage customer support tickets</p>
        </div>
        <div class="@if($user->role === 'noc') bg-orange-50 border-orange-200 @else bg-gray-50 border-gray-200 @endif border rounded-lg p-4">
            <span class="inline-flex items-center px-2 py-1 rounded bg-orange-100 text-orange-800 font-medium mb-2">NOC</span>
            <p class="text-gray-600 @if($user->role === 'noc') font-medium @endif">Manage routers and network operations</p>
        </div>
    </div>
</div>
@endsection
