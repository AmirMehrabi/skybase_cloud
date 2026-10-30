<footer class="border-t border-white/10 bg-[#17211f] text-white">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1fr_auto_auto] lg:px-8">
            <div>
                <a href="{{ route('home') }}" class="inline-flex focus:outline-none focus:ring-2 focus:ring-[#f5c542] focus:ring-offset-4 focus:ring-offset-[#17211f]" aria-label="SkyBase Cloud home">
                    <img src="{{ asset('assets/images/logo/logo-white.png') }}" width="300" height="75" loading="lazy" decoding="async" alt="SkyBase Cloud" class="h-8 w-auto">
                </a>
                <p class="mt-4 max-w-sm text-sm leading-6 text-white/60">Straightforward ISP operations for MikroTik networks.</p>
            </div>

            <div class="space-y-2 text-sm">
                <p class="font-semibold text-white">Product</p>
                <a href="{{ route('features') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Features</a>
                <a href="{{ route('seo.wisp-management-software') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">WISP management software</a>
                <a href="{{ route('seo.wisp-crm') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">WISP CRM</a>
                <a href="{{ route('seo.isp-crm') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">ISP CRM</a>
                <a href="{{ route('seo.isp-billing-software') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">ISP billing software</a>
                <a href="{{ route('seo.mikrotik-isp-software') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">MikroTik ISP software</a>
                <a href="{{ route('pricing') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Pricing</a>
                <a href="{{ route('changelog') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Changelog</a>
                <a href="{{ route('auth.register') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Start free</a>
            </div>

            <div class="space-y-2 text-sm">
                <p class="font-semibold text-white">Talk to us</p>
                <a href="{{ route('contact.show') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Contact</a>
                <a href="{{ route('alternatives.splynx') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Splynx alternative</a>
                <a href="{{ route('alternatives.sonar') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Sonar alternative</a>
                <a href="{{ route('alternatives.powercode') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Powercode alternative</a>
                <a href="{{ route('guides.choosing-isp-crm') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Choosing an ISP CRM</a>
                <a href="{{ route('guides.isp-software-migration') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">Migration checklist</a>
                <a href="{{ route('guides.mikrotik-billing') }}" class="block text-white/60 transition hover:text-white motion-reduce:transition-none">MikroTik billing guide</a>
            </div>
        </div>

        <div class="mx-auto flex max-w-6xl flex-col justify-between gap-3 border-t border-white/10 px-4 py-6 text-xs text-white/50 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} SkyBase Cloud</p>
            <a href="mailto:support@skybase.app" class="transition hover:text-white motion-reduce:transition-none">support@skybase.app</a>
        </div>
    </footer>
