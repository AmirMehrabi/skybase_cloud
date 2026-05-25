@extends('layouts.admin')

@section('title', 'Edit Site')

@section('content')
<div class="space-y-6 pb-24">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Site</h1>
            <p class="text-sm text-gray-500 mt-1">Updating {{ $site->name }}</p>
        </div>
    </div>

    <x-form.validation-summary :errors="$errors" />

    <form action="{{ route('sites.update', $site) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('sites.partials.form')

        <div class="fixed bottom-0 right-0 left-0 lg:left-64 bg-white border-t border-gray-200 shadow-lg p-4 z-40">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('sites.show', $site) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Update Site</button>
            </div>
        </div>
    </form>
</div>
@endsection
