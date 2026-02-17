@props([
    'columns' => [],
    'rows' => [],
    'empty' => 'No data available',
    'emptyIcon' => null,
])

<div class="overflow-x-auto rounded-xl border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @foreach($columns as $column)
                    <th scope="col"
                        class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider {{ $column['class'] ?? '' }}">
                        @if(isset($column['sortable']))
                            <button class="flex items-center gap-1 group hover:text-gray-700">
                                {{ $column['label'] }}
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </button>
                        @else
                            {{ $column['label'] }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($rows as $row)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    @foreach($columns as $key => $column)
                        <td class="px-6 py-4 whitespace-nowrap {{ $column['class'] ?? '' }}">
                            @isset($column['render'])
                                {{ $column['render']($row) }}
                            @else
                                {{ $row[$key] ?? '' }}
                            @endisset
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            @if($emptyIcon)
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                </div>
                            @endif
                            <p class="text-sm text-gray-500">{{ $empty }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
