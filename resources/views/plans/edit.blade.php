@extends('layouts.admin')

@section('title', 'Edit Plan')

@php
$plan = [
    'id' => $id,
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
];
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Plan</h1>
            <p class="text-sm text-gray-500 mt-1">Update plan: {{ $plan['name'] }}</p>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('plans.index') }}" class="hover:text-gray-700">Plans</a>
        <span class="mx-2">/</span>
        <a href="{{ route('plans.show', $id) }}" class="hover:text-gray-700">{{ $plan['name'] }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Edit</span>
    </nav>

    <!-- Form -->
    <form action="{{ route('plans.show', $id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('plans.partials.form')
    </form>
</div>
@endsection
