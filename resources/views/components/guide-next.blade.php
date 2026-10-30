@props(['anchor', 'label'])

<a href="#{{ $anchor }}" class="mt-6 inline-flex min-h-11 items-center gap-3 rounded-xl border border-[#145a5a]/25 px-5 py-3 text-sm font-semibold text-[#145a5a] transition hover:bg-[#145a5a]/5 focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-2 motion-reduce:transition-none">
    Next: {{ $label }} <span aria-hidden="true">→</span>
</a>
