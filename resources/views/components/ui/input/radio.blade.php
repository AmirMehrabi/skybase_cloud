@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'value' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
])

<div class="space-y-3">
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span>@endif
        </label>
    @endif

    <div class="space-y-2">
        @foreach($options as $key => $option)
            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $key }}"
                    @if((string)$value === (string)$key) checked @endif
                    @if($required) required @endif
                    @if($disabled) disabled @endif
                    {{ $attributes->merge(['class' => 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300']) }}
                />
                <span class="text-sm text-gray-700">{{ $option }}</span>
            </label>
        @endforeach
    </div>

    @if($error)
        <p class="text-sm text-red-600 mt-1">{{ $error }}</p>
    @endif
</div>
