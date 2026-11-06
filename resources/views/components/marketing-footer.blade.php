@php
    $footerSections = [
        'Product' => [
            ['route' => 'features', 'label' => 'Features'],
            ['route' => 'pricing', 'label' => 'Pricing'],
            ['route' => 'changelog', 'label' => 'Changelog'],
            ['route' => 'auth.register', 'label' => 'Start free'],
        ],
        'Solutions' => [
            ['route' => 'seo.isp-crm', 'label' => 'ISP CRM'],
            ['route' => 'seo.wisp-crm', 'label' => 'WISP CRM'],
            ['route' => 'seo.wisp-management-software', 'label' => 'WISP management software'],
            ['route' => 'seo.isp-billing-software', 'label' => 'ISP billing software'],
            ['route' => 'seo.mikrotik-isp-software', 'label' => 'MikroTik ISP software'],
        ],
        'Guides' => [
            ['route' => 'guide', 'label' => 'First subscription guide'],
            ['route' => 'guides.choosing-isp-crm', 'label' => 'Choosing an ISP CRM'],
            ['route' => 'guides.isp-software-migration', 'label' => 'Migration checklist'],
            ['route' => 'guides.mikrotik-billing', 'label' => 'MikroTik billing guide'],
        ],
        'Compare' => [
            ['route' => 'alternatives.visp', 'label' => 'Visp alternative'],
            ['route' => 'alternatives.splynx', 'label' => 'Splynx alternative'],
            ['route' => 'alternatives.sonar', 'label' => 'Sonar alternative'],
            ['route' => 'alternatives.powercode', 'label' => 'Powercode alternative'],
        ],
    ];
@endphp

<footer class="border-t border-white/10 bg-[#17211f] text-white">
    <div class="mx-auto grid max-w-6xl gap-12 px-4 py-12 sm:px-6 lg:grid-cols-[0.8fr_2fr] lg:px-8 lg:py-16">
        <div>
            <a href="{{ route('home') }}" class="inline-flex rounded-sm focus:outline-none focus:ring-2 focus:ring-[#f5c542] focus:ring-offset-4 focus:ring-offset-[#17211f]" aria-label="SkyBase Cloud home">
                <img src="{{ asset('assets/images/logo/logo-white.png') }}" width="300" height="75" loading="lazy" decoding="async" alt="SkyBase Cloud" class="h-8 w-auto">
            </a>
            <p class="mt-4 max-w-sm text-sm leading-6 text-white/70">Straightforward ISP operations for MikroTik networks.</p>
            <div class="mt-6 flex flex-col items-start gap-3 text-sm">
                <a href="{{ route('contact.show') }}" class="rounded-sm font-semibold text-white underline decoration-white/30 underline-offset-4 transition hover:decoration-white focus:outline-none focus:ring-2 focus:ring-[#f5c542] focus:ring-offset-4 focus:ring-offset-[#17211f] motion-reduce:transition-none">Contact us</a>
                <a href="mailto:support@skybase.app" class="rounded-sm text-white/70 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[#f5c542] focus:ring-offset-4 focus:ring-offset-[#17211f] motion-reduce:transition-none">support@skybase.app</a>
            </div>
        </div>

        <nav aria-label="Footer navigation" class="grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($footerSections as $sectionLabel => $sectionLinks)
                <section aria-labelledby="footer-{{ Str::slug($sectionLabel) }}">
                    <h2 id="footer-{{ Str::slug($sectionLabel) }}" class="text-sm font-semibold text-white">{{ $sectionLabel }}</h2>
                    <ul class="mt-4 flex flex-col gap-3 text-sm">
                        @foreach($sectionLinks as $sectionLink)
                            <li>
                                <a href="{{ route($sectionLink['route']) }}" class="inline-block rounded-sm leading-6 text-white/70 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-[#f5c542] focus:ring-offset-4 focus:ring-offset-[#17211f] motion-reduce:transition-none">{{ $sectionLink['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </nav>
    </div>

    <div class="mx-auto max-w-6xl border-t border-white/10 px-4 py-6 text-xs text-white/60 sm:px-6 lg:px-8">
        <p>&copy; {{ now()->year }} SkyBase Cloud</p>
    </div>
</footer>
