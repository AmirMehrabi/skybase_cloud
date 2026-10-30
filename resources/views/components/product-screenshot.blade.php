@props(['image', 'heading', 'caption'])

<section class="border-b border-[#17211f]/10 bg-[#fffdf8]">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#145a5a]">Inside SkyBase</p>
        <h2 class="mt-4 max-w-3xl text-3xl font-bold tracking-tight sm:text-4xl">{{ $heading }}</h2>
        <figure class="mt-8 max-w-4xl">
            <a href="{{ asset('images/product/'.$image.'.webp') }}" class="block rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#145a5a] focus:ring-offset-4" aria-label="Open full-size SkyBase screenshot">
                <img src="{{ asset('images/product/'.$image.'.webp') }}" alt="{{ $caption }} Private details are redacted." width="905" height="605" loading="lazy" decoding="async" class="h-auto w-full rounded-2xl border border-[#17211f]/15">
            </a>
            <figcaption class="mt-4 max-w-3xl text-sm leading-6 text-[#52605d]">{{ $caption }} Captured from a working instance. Private details have been redacted. Select the image to see it full size.</figcaption>
        </figure>
    </div>
</section>
