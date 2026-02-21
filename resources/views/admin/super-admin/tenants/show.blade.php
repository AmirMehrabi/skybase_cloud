@extends('layouts.admin')

@section('title', 'Tenant Details')

@push('styles')
<style>
    .stat-card {
        @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.super-admin.tenants.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-2">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Tenants</span>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $tenant->company_name }}</h1>
            <p class="text-gray-600 mt-1">{{ $tenant->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.super-admin.tenants.edit', $tenant) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                <i class="fas fa-edit"></i>
                <span>Edit</span>
            </a>
            @if($tenant->isActive())
            <form method="POST" action="{{ route('admin.super-admin.tenants.suspend', $tenant) }}" class="inline" onsubmit="return confirm('Are you sure you want to suspend this tenant?')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 flex items-center gap-2">
                    <i class="fas fa-pause"></i>
                    <span>Suspend</span>
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.super-admin.tenants.activate', $tenant) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 flex items-center gap-2">
                    <i class="fas fa-play"></i>
                    <span>Activate</span>
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $userCount }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Customers</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $customerCount }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Routers</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $routerCount }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-server text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Status</p>
                <p class="text-xl font-bold mt-2 @if($tenant->status === 'active') text-green-600 @elseif($tenant->status === 'pending') text-yellow-600 @else text-red-600 @endif">
                    {{ ucfirst($tenant->status) }}
                </p>
            </div>
            <div class="w-12 h-12 @if($tenant->status === 'active') bg-green-100 @elseif($tenant->status === 'pending') bg-yellow-100 @else bg-red-100 @endif rounded-lg flex items-center justify-center">
                <i class="fas @if($tenant->status === 'active') fa-check-circle text-green-600 @elseif($tenant->status === 'pending') fa-clock text-yellow-600 @else fa-ban text-red-600 @endif text-xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Company Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->company_name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Contact Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Country</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->country ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Timezone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->timezone }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tenant ID</dt>
                    <dd class="mt-1 text-sm text-gray-500 font-mono">{{ $tenant->id }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Slug</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenant->slug }}</dd>
                </div>
            </dl>
        </div>

        <!-- Subscription Info -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h3>
            @if($tenant->plan)
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Current Plan</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $tenant->plan->name }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Price</dt>
                    <dd class="mt-1 text-sm text-gray-900">${{ number_format($tenant->plan->price, 2) }} / {{ $tenant->plan->billing_cycle }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 mb-2">Plan Limits</dt>
                    <dd class="grid grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($tenant->plan->max_customers) }}</div>
                            <div class="text-xs text-gray-500">Max Customers</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($tenant->plan->max_routers) }}</div>
                            <div class="text-xs text-gray-500">Max Routers</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($tenant->plan->max_users) }}</div>
                            <div class="text-xs text-gray-500">Max Users</div>
                        </div>
                    </dd>
                </div>
            </dl>
            @else
            <p class="text-gray-500">No plan assigned</p>
            @endif

            <!-- Trial Info -->
            @if($tenant->trial_ends_at)
            <div class="mt-4 p-4 rounded-lg @if($tenant->isOnTrial()) bg-purple-50 border border-purple-200 @elseif($tenant->hasExpiredTrial()) bg-red-50 border border-red-200 @else bg-gray-50 border border-gray-200 @endif">
                <div class="flex items-center justify-between">
                    <div>
                        <dt class="text-sm font-medium @if($tenant->isOnTrial()) text-purple-900 @elseif($tenant->hasExpiredTrial()) text-red-900 @else text-gray-900 @endif">Trial Period</dt>
                        <dd class="mt-1 text-sm @if($tenant->isOnTrial()) text-purple-700 @elseif($tenant->hasExpiredTrial()) text-red-700 @else text-gray-700 @endif">
                            @if($tenant->isOnTrial())
                                Active - Expires {{ $tenant->trial_ends_at->diffForHumans() }}
                            @elseif($tenant->hasExpiredTrial())
                                Expired {{ $tenant->trial_ends_at->diffForHumans() }}
                            @else
                                {{ $tenant->trial_ends_at->format('M d, Y') }}
                            @endif
                        </dd>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Users List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Users ({{ $tenant->users->count() }})</h3>
            <div class="space-y-3">
                @foreach($tenant->users as $user)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($user->role) }}
                        </span>
                        @if($user->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center justify-center gap-2">
                    <i class="fas fa-file-invoice"></i>
                    <span>View Invoices</span>
                </button>
                <button class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center justify-center gap-2">
                    <i class="fas fa-chart-line"></i>
                    <span>View Analytics</span>
                </button>
                <button class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center justify-center gap-2">
                    <i class="fas fa-cog"></i>
                    <span>Manage Settings</span>
                </button>
            </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Timeline</h3>
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Tenant created</p>
                        <p class="text-xs text-gray-500">{{ $tenant->created_at->format('M d, Y \a\t g:i A') }}</p>
                    </div>
                </div>
                @if($tenant->updated_at->ne($tenant->created_at))
                <div class="flex gap-3">
                    <div class="w-2 h-2 bg-gray-400 rounded-full mt-2"></div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Last updated</p>
                        <p class="text-xs text-gray-500">{{ $tenant->updated_at->format('M d, Y \a\t g:i A') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
