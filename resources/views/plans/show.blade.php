@extends('layouts.admin')

@section('title', 'Plan Details')

@php
$plan = [
    'id' => 1,
    'name' => 'Home 50 Mbps',
    'internal_name' => 'home_50',
    'description' => 'Standard residential internet plan',
    'status' => 'active',
    'visibility' => 'public',
    'type' => 'pppoe',
    'category' => 'Residential',
    'download_speed' => 50,
    'upload_speed' => 10,
    'burst_download' => 80,
    'burst_upload' => 20,
    'bandwidth_unit' => 'Mbps',
    'data_limit' => 500,
    'data_unit' => 'GB',
    'unlimited' => false,
    'price' => 25.00,
    'currency' => 'USD',
    'billing_cycle' => 'monthly',
    'setup_fee' => 10.00,
    'tax_profile' => 'Standard VAT',
    'router_profile' => 'Mikrotik PPPoE Basic',
    'ip_pool' => 'Dynamic Pool',
    'priority' => 5,
    'contract_required' => false,
    'contract_duration' => 0,
    'available_from' => '2025-01-01',
    'available_to' => null,
    'notes' => 'Best for normal home usage',
    'subscribers' => 124,
    'created_at' => '2025-01-01',
    'updated_at' => '2025-02-01',
];

function getStatusBadgeClass($status)
{
    $classes = [
        'active' => 'bg-green-100 text-green-800 border-green-200',
        'inactive' => 'bg-gray-100 text-gray-800 border-gray-200',
        'archived' => 'bg-red-100 text-red-800 border-red-200',
    ];

    return $classes[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}

function getVisibilityBadgeClass($visibility)
{
    $classes = [
        'public' => 'bg-blue-100 text-blue-800 border-blue-200',
        'private' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'hidden' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    return $classes[$visibility] ?? 'bg-gray-100 text-gray-800 border-gray-200';
}
@endphp

@section('content')
<div class="space-y-6">
    <!-- Top Header Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Plan Info -->
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $plan['name'] }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border {{ getStatusBadgeClass($plan['status']) }}">
                            {{ ucfirst($plan['status']) }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium border {{ getVisibilityBadgeClass($plan['visibility']) }}">
                            {{ ucfirst($plan['visibility']) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-gray-500">
                        <span>{{ $plan['internal_name'] }}</span>
                        <span>&bull;</span>
                        <span>{{ $plan['category'] }}</span>
                        <span>&bull;</span>
                        <span>{{ ucfirst($plan['type']) }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center gap-3">
                <a href="{{ route('plans.edit', $id) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Plan
                </a>

                @if($plan['status'] === 'active')
                    <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Disable Plan
                    </button>
                @else
                    <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Enable Plan
                    </button>
                @endif

                <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Plan
                </button>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mt-6 pt-6 border-t border-gray-200">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Price</p>
                <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($plan['price'], 2) }} / {{ $plan['billing_cycle'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Speed</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ $plan['download_speed'] }} / {{ $plan['upload_speed'] }} {{ $plan['bandwidth_unit'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Subscribers</p>
                <p class="text-xl font-bold text-gray-900 mt-1">{{ $plan['subscribers'] }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Data Limit</p>
                <p class="text-xl font-bold text-gray-900 mt-1">
                    @if($plan['unlimited'])
                        Unlimited
                    @else
                        {{ $plan['data_limit'] }} {{ $plan['data_unit'] }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Future-Ready Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Clone Plan
            </button>
            <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                View Subscribers
            </button>
            <button class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-teal-700 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                View Usage Analytics
            </button>
        </div>
    </div>

    <!-- Detailed Information Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                <p class="text-sm text-gray-500 mt-1">General plan details</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Plan Name</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['name'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Internal Name</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['internal_name'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Description</dt>
                    <dd class="text-sm text-gray-900 max-w-xs text-right">{{ $plan['description'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getStatusBadgeClass($plan['status']) }}">{{ ucfirst($plan['status']) }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Visibility</dt>
                    <dd><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ getVisibilityBadgeClass($plan['visibility']) }}">{{ ucfirst($plan['visibility']) }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Category</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['category'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Network Configuration -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Network Configuration</h3>
                <p class="text-sm text-gray-500 mt-1">Service type settings</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Service Type</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ ucfirst($plan['type']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Router Profile</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['router_profile'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">IP Pool</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['ip_pool'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Priority</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['priority'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Speed Configuration -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Speed Configuration</h3>
                <p class="text-sm text-gray-500 mt-1">Bandwidth settings</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Download Speed</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['download_speed'] }} {{ $plan['bandwidth_unit'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Upload Speed</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['upload_speed'] }} {{ $plan['bandwidth_unit'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Burst Download</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['burst_download'] }} {{ $plan['bandwidth_unit'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Burst Upload</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['burst_upload'] }} {{ $plan['bandwidth_unit'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Data Limit -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Data Limit</h3>
                <p class="text-sm text-gray-500 mt-1">Usage restrictions</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Unlimited</dt>
                    <dd class="text-sm font-medium text-gray-900">
                        @if($plan['unlimited'])
                            <span class="text-green-600">Yes</span>
                        @else
                            <span class="text-gray-600">No</span>
                        @endif
                    </dd>
                </div>
                @if(!$plan['unlimited'])
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Data Limit</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['data_limit'] }} {{ $plan['data_unit'] }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Billing -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Billing</h3>
                <p class="text-sm text-gray-500 mt-1">Pricing information</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Price</dt>
                    <dd class="text-sm font-medium text-gray-900">${{ number_format($plan['price'], 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Currency</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['currency'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Billing Cycle</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ ucfirst($plan['billing_cycle']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Setup Fee</dt>
                    <dd class="text-sm font-medium text-gray-900">${{ number_format($plan['setup_fee'], 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Tax Profile</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['tax_profile'] }}</dd>
                </div>
            </dl>
        </div>

        <!-- Contract -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Contract</h3>
                <p class="text-sm text-gray-500 mt-1">Contract requirements</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Contract Required</dt>
                    <dd class="text-sm font-medium text-gray-900">
                        @if($plan['contract_required'])
                            <span class="text-green-600">Yes</span>
                        @else
                            <span class="text-gray-600">No</span>
                        @endif
                    </dd>
                </div>
                @if($plan['contract_required'])
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Contract Duration</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $plan['contract_duration'] }} months</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Availability -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Availability</h3>
                <p class="text-sm text-gray-500 mt-1">Plan availability dates</p>
            </div>
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Available From</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($plan['available_from'])->format('M d, Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Available To</dt>
                    <dd class="text-sm font-medium text-gray-900">
                        @if($plan['available_to'])
                            {{ \Carbon\Carbon::parse($plan['available_to'])->format('M d, Y') }}
                        @else
                            <span class="text-gray-400">No end date</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Notes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Notes</h3>
                <p class="text-sm text-gray-500 mt-1">Additional information</p>
            </div>
            <p class="text-sm text-gray-900">{{ $plan['notes'] }}</p>
        </div>

        <!-- Metadata -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Metadata</h3>
                <p class="text-sm text-gray-500 mt-1">Timestamp information</p>
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Created At</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($plan['created_at'])->format('M d, Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Updated At</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($plan['updated_at'])->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
