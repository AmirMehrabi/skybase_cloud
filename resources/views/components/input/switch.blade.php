@props([
    'id' => null,
    'name' => null,
    'label' => null,
    'checked' => false,
    'xModel' => null,
    'help' => null,
])

<div>
    <div class="flex items-center justify-between">
        <div>
            @if($label)
            <label for="{{ $id }}" class="text-sm font-medium text-gray-900">{{ $label }}</label>
            @endif
            @if($help)
            <p class="text-xs text-gray-500 mt-1">{{ $help }}</p>
            @endif
        </div>
        <div class="flex items-center">
            <button
                type="button"
                id="{{ $id }}"
                @if($xModel)
                    @click="{{ $xModel }} = !{{ $xModel }}"
                    :class="{{ $xModel }} ? 'bg-blue-600' : 'bg-gray-300'"
                @else
                    @click="$el.querySelector('input').click()"
                    :class="$el.querySelector('input').checked ? 'bg-blue-600' : 'bg-gray-300'"
                @endif
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                role="switch"
                aria-checked="{{ $checked ? 'true' : 'false' }}"
            >
                <span class="sr-only">{{ $label }}</span>
                <span
                    @if($xModel)
                        :class="{{ $xModel }} ? 'translate-x-5' : 'translate-x-0'"
                    @else
                        :class="$el.querySelector('input').checked ? 'translate-x-5' : 'translate-x-0'"
                    @endif
                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                ></span>
                <input
                    type="hidden"
                    name="{{ $name }}"
                    value="1"
                    @if($checked || old($name)) checked @endif
                    @if($xModel) x-model="{{ $xModel }}" @endif
                >
            </button>
        </div>
    </div>
</div>
