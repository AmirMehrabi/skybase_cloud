@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tenant.roles.index') }}" class="mb-2 flex items-center gap-2 text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Roles</span>
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Create Role</h1>
    <p class="mt-1 text-gray-600">Define a role and select its module permissions.</p>
</div>

<form method="POST" action="{{ route('admin.tenant.roles.store') }}" class="space-y-6">
    @csrf
    @include('admin.tenant.roles.partials.form')

    <div class="flex items-center justify-end gap-4">
        <a href="{{ route('admin.tenant.roles.index') }}" class="rounded-lg border border-gray-300 px-6 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">Create Role</button>
    </div>
</form>
@endsection
