@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'value' => null,
    'required' => false,
    'xModel' => null,
    'xChange' => null,
    'help' => null,
])

@php
$cidrOptions = [
    8 => '8 (16,777,214 IPs)',
    16 => '16 (65,534 IPs)',
    20 => '20 (4,094 IPs)',
    21 => '21 (2,046 IPs)',
    22 => '22 (1,022 IPs)',
    23 => '23 (510 IPs)',
    24 => '24 (254 IPs)',
    25 => '25 (126 IPs)',
    26 => '26 (62 IPs)',
    27 => '27 (30 IPs)',
    28 => '28 (14 IPs)',
    29 => '29 (6 IPs)',
    30 => '30 (2 IPs)',
    31 => '31 (2 IPs)',
    32 => '32 (1 IP)',
];
@endphp

<div class="mb-4">
    @if($label)
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>
    @endif

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($xModel) x-model="{{ $xModel }}" @endif
        @if($xChange) @change="{{ $xChange }}" @endif
        @error($name)
            class="block w-full px-3 py-2 bg-white border border-red-500 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
        @else
            class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition"
        @enderror
    >
        <option value="">Select CIDR</option>
        @foreach($cidrOptions as $cidr => $label)
        <option value="{{ $cidr }}" {{ old($name, $value) == $cidr ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    @if($help)
        <p class="mt-1 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
