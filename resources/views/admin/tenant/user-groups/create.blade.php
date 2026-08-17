@extends('layouts.admin')

@section('title', 'Create User Group')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tenant.user-groups.index') }}" class="mb-2 flex items-center gap-2 text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left"></i><span>Back to User Groups</span></a>
    <h1 class="text-2xl font-bold text-gray-900">Create User Group</h1>
    <p class="mt-1 text-gray-600">Create an isolated data partition inside this tenant.</p>
</div>

<form method="POST" action="{{ route('admin.tenant.user-groups.store') }}" class="space-y-6">
    @csrf
    @include('admin.tenant.user-groups.partials.form')
    <div class="flex justify-end gap-4">
        <a href="{{ route('admin.tenant.user-groups.index') }}" class="rounded-lg border border-gray-300 px-6 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">Create User Group</button>
    </div>
</form>
@endsection
