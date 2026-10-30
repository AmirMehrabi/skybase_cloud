@props(['chapters', 'serviceName'])

<nav aria-label="Guide steps" class="rounded-xl border border-[#17211f]/10 bg-[#f7f3ea] p-4 lg:sticky lg:top-28">
                <p class="mb-3 text-xs font-bold uppercase tracking-widest text-[#52605d]">Your {{ $serviceName }} journey</p>
                <ol class="grid grid-cols-1 gap-1 sm:grid-cols-2 lg:grid-cols-1">
                    @foreach($chapters as $anchor => $label)
                        <li><a x-on:click="$el.closest('details')?.removeAttribute('open')" href="#{{ $anchor }}" :aria-current="currentChapter === '{{ $anchor }}' ? 'location' : null" :class="currentChapter === '{{ $anchor }}' ? 'bg-white ring-1 ring-[#145a5a]/30' : ''" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-[#0d2f35] hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#145a5a]">{{ $label }}</a></li>
                    @endforeach
                </ol>
                <p class="mt-4 border-t border-[#17211f]/10 pt-4 text-xs leading-5 text-[#52605d]">Setup links open in a new tab, so you can keep this guide beside your workspace.</p>
            </nav>
