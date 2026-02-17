@extends('layouts.admin')

@section('title', 'Create Plan')

@php
$plan = [];
@endphp

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Plan</h1>
            <p class="text-sm text-gray-500 mt-1">Create a new service plan</p>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500">
        <a href="{{ route('plans.index') }}" class="hover:text-gray-700">Plans</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Create</span>
    </nav>

    <!-- Form -->
    <form action="{{ route('plans.index') }}" method="POST">
        @csrf
        @include('plans.partials.form')
    </form>
</div>
@endsection
