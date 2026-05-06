@props([
    'activities' => collect(),
    'emptyMessage' => 'No activity recorded yet.',
])

@php
    $items = collect($activities);
@endphp

@if($items->isEmpty())
    <div class="text-center py-8 text-gray-400">
        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm">{{ $emptyMessage }}</p>
    </div>
@else
    <div class="space-y-6">
        @foreach($items as $index => $activity)
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full {{ $activity['iconColor'] ?? 'bg-gray-100 text-gray-600' }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $activity['icon'] ?? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"></path>' !!}</svg>
                    </div>
                    @if($index < $items->count() - 1)
                        <div class="w-0.5 h-full bg-gray-200 mt-2"></div>
                    @endif
                </div>
                <div class="flex-1 pb-6 @if($index < $items->count() - 1) border-b border-gray-100 @endif">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $activity['title'] ?? $activity['action'] ?? 'Activity logged' }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ $activity['description'] ?? '' }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs text-gray-400">{{ $activity['user'] ?? 'System' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $activity['time'] ?? $activity['timestamp'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
