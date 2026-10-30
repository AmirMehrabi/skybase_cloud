@props(['image', 'alt', 'caption'])

<figure class="mt-6 overflow-hidden rounded-xl border border-[#17211f]/15 bg-white">
    <a href="{{ asset('images/guide/'.$image.'.webp') }}" target="_blank" rel="noopener" class="block focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#145a5a]" aria-label="{{ $alt }} — open full screenshot in a new tab">
        <img src="{{ asset('images/guide/'.$image.'.webp') }}" alt="{{ $alt }}" width="960" loading="lazy" decoding="async" class="max-h-80 w-full object-cover object-top">
    </a>
    <figcaption class="border-t border-[#17211f]/10 px-4 py-3 text-sm leading-6 text-[#52605d]">
        {{ $caption }}
        <details class="mt-2">
            <summary class="cursor-pointer py-2 font-semibold text-[#145a5a] focus:outline-none focus:ring-2 focus:ring-[#145a5a]">Show the full screenshot</summary>
            <a href="{{ asset('images/guide/'.$image.'.webp') }}" target="_blank" rel="noopener" class="block" aria-label="Open full screenshot in a new tab">
                <img src="{{ asset('images/guide/'.$image.'.webp') }}" alt="{{ $alt }}" width="960" loading="lazy" decoding="async" class="mt-3 h-auto w-full">
            </a>
        </details>
    </figcaption>
</figure>
