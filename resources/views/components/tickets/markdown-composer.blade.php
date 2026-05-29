@props([
    'id',
    'name',
    'label' => 'Message',
    'value' => null,
    'rows' => 6,
    'required' => false,
])

@php
    $editorId = $id.'-editor';
    $height = max(220, (int) $rows * 42).'px';
@endphp

<div class="mb-4">
    <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-slate-700">
        {{ $label }}
        @if($required)<span class="text-red-500">*</span>@endif
    </label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        @if($required) required @endif
        class="sr-only"
    >{{ old($name, $value) }}</textarea>

    <div
        id="{{ $editorId }}"
        data-ticket-editor
        data-target="{{ $id }}"
        data-height="{{ $height }}"
        class="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm @error($name) border-red-500 @enderror"
    ></div>

    <p class="mt-1 text-xs text-slate-500">Use the toolbar for rich text. Content is stored as Markdown for clean rendering.</p>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('styles')
    <style>
        .toastui-editor-defaultUI {
            border: 0 !important;
            font-family: inherit;
        }

        .toastui-editor-toolbar {
            background: rgb(248 250 252);
        }

        .toastui-editor-contents,
        .toastui-editor-md-container .toastui-editor-md-preview,
        .toastui-editor-md-container .toastui-editor {
            font-family: inherit;
            font-size: 0.925rem;
        }

        .toastui-editor-contents p,
        .toastui-editor-contents blockquote {
            margin-bottom: 0.75rem;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        window.initializeTicketEditors?.();
    </script>
    @endpush
@endonce
