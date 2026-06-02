@extends('layouts.admin')

@section('title', 'Import / Export Report')

@php
$statusClasses = [
    'queued' => 'bg-slate-100 text-slate-700 border-slate-200',
    'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
    'completed' => 'bg-green-100 text-green-700 border-green-200',
    'failed' => 'bg-red-100 text-red-700 border-red-200',
    'created' => 'bg-green-100 text-green-700 border-green-200',
    'updated' => 'bg-blue-100 text-blue-700 border-blue-200',
    'skipped' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
];
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Import / Export Report</h1>
            <p class="mt-1 text-sm text-gray-500">{{ ucfirst($run->module) }} {{ ucfirst($run->direction) }} #{{ $run->id }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($run->direction === 'export' && $run->status === 'completed')
                <a href="{{ route('import-export.download', [$run->module, $run]) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Download
                </a>
            @endif
            <a href="{{ route($run->module.'.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">Status</p>
            <span class="mt-3 inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$run->status] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">{{ ucfirst($run->status) }}</span>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">Rows</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $run->processed_rows }} / {{ $run->total_rows }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">Created</p>
            <p class="mt-2 text-2xl font-bold text-green-700">{{ $run->created_count }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">Updated</p>
            <p class="mt-2 text-2xl font-bold text-blue-700">{{ $run->updated_count }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">Skipped</p>
            <p class="mt-2 text-2xl font-bold text-yellow-700">{{ $run->skipped_count }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">Failed</p>
            <p class="mt-2 text-2xl font-bold text-red-700">{{ $run->failed_count }}</p>
        </div>
    </div>

    @if ($run->error)
        <x-ui.alert type="error" :message="$run->error" />
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900">Row Details</h2>
            <p class="mt-1 text-sm text-gray-500">Per-row result, action, identifier, and validation message.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Row</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Identifier</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $row->row_number }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$row->status] ?? 'bg-red-100 text-red-700 border-red-200' }}">{{ ucfirst($row->status) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $row->identifier ?: '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">{{ $row->action ?: '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $row->message }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No row details recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-6 py-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
