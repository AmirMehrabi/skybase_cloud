@extends('layouts.admin')

@section('title', 'Edit User Group')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tenant.user-groups.show', $userGroup) }}" class="mb-2 flex items-center gap-2 text-blue-600 hover:text-blue-800"><i class="fas fa-arrow-left"></i><span>Back to User Group</span></a>
    <h1 class="text-2xl font-bold text-gray-900">Edit User Group</h1>
</div>

<form method="POST" action="{{ route('admin.tenant.user-groups.update', $userGroup) }}" class="space-y-6">
    @csrf
    @method('put')
    @include('admin.tenant.user-groups.partials.form')
    <div class="flex justify-end gap-4">
        <a href="{{ route('admin.tenant.user-groups.show', $userGroup) }}" class="rounded-lg border border-gray-300 px-6 py-2 text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">Save Changes</button>
    </div>
</form>
@endsection
