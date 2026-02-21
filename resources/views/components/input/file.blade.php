@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'accept' => null,
    'required' => false,
    'help' => null,
])

<div>
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif

    @if($help)
    <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif

    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($required) required @endif
        class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
    >

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
