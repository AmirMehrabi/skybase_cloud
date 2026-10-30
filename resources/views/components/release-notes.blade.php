@props(['sections'])

<div class="space-y-7 border-t border-[#17211f]/10 pt-6">
    @foreach($sections as $sectionTitle => $items)
        <section>
            <h3 class="text-base font-bold text-[#145a5a]">{{ $sectionTitle === 'Changed' ? 'Improved' : $sectionTitle }}</h3>
            <ul class="mt-3 list-disc space-y-3 pl-5 text-sm leading-7 text-[#52605d] marker:text-[#145a5a]">
                @foreach($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
