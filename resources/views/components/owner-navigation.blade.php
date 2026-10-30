<nav aria-label="Account navigation" class="mb-6 flex flex-wrap gap-4 border-b border-gray-200 pb-4">
    @if(auth()->user()?->isCentralAdmin())
        <a href="{{ route('admin.super-admin.tenants.index') }}" class="font-semibold text-teal-700">SkyBase owner console</a>
    @elseif(auth()->user()?->isOwner() && config('skybase_billing.enabled'))
        <a href="{{ route('skybase.billing.index') }}" class="font-semibold text-teal-700">SkyBase subscription</a>
    @endif
</nav>
