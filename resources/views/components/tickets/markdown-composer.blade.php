@props([
    'id',
    'name',
    'label' => 'Message',
    'value' => null,
    'rows' => 6,
    'required' => false,
])

<div class="mb-4" x-data="ticketMarkdownComposer('{{ $id }}')">
    <div class="mb-1 flex items-center justify-between gap-3">
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
        <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
            <button type="button" class="rounded px-2 py-1 text-xs font-bold text-slate-700 hover:bg-white" title="Bold" @click="wrap('**', '**')">B</button>
            <button type="button" class="rounded px-2 py-1 text-xs italic text-slate-700 hover:bg-white" title="Italic" @click="wrap('*', '*')">I</button>
            <button type="button" class="rounded px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-white" title="Quote" @click="quote()">Quote</button>
        </div>
    </div>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if($required) required @endif
        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-3 font-mono text-sm leading-6 text-slate-950 shadow-sm focus:border-transparent focus:ring-2 focus:ring-emerald-600 @error($name) border-red-500 @enderror"
    >{{ old($name, $value) }}</textarea>

    <p class="mt-1 text-xs text-slate-500">Markdown supported: bold, italic, quotes, lists, and line breaks.</p>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
        function ticketMarkdownComposer(textareaId) {
            return {
                textarea() {
                    return document.getElementById(textareaId);
                },
                wrap(before, after) {
                    const textarea = this.textarea();
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const selected = textarea.value.slice(start, end) || 'text';
                    textarea.value = textarea.value.slice(0, start) + before + selected + after + textarea.value.slice(end);
                    textarea.focus();
                    textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
                },
                quote() {
                    const textarea = this.textarea();
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const selected = textarea.value.slice(start, end) || 'quoted text';
                    const quoted = selected.split('\n').map((line) => `> ${line}`).join('\n');
                    textarea.value = textarea.value.slice(0, start) + quoted + textarea.value.slice(end);
                    textarea.focus();
                    textarea.setSelectionRange(start, start + quoted.length);
                },
            };
        }
    </script>
    @endpush
@endonce
