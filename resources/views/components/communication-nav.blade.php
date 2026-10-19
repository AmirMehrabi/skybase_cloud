<nav class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-4" aria-label="Communications">
    @foreach(['campaigns.index' => 'Campaigns', 'communications.index' => 'Templates & rules', 'communications.deliveries' => 'Delivery activity', 'communications.incidents' => 'Incidents', 'settings.sms' => 'SMS gateway'] as $destination => $label)
        @if(auth()->user()->canAccessRoute($destination))
            <a href="{{ route($destination) }}" @class(['rounded-lg px-3 py-2 text-sm font-medium', 'bg-teal-900 text-white' => request()->routeIs($destination), 'bg-gray-100 text-gray-700 hover:bg-gray-200' => !request()->routeIs($destination)])>{{ $label }}</a>
        @endif
    @endforeach
    <a href="{{ route('settings.index', ['tab' => 'email']) }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700">Email settings</a>
</nav>
@if($errors->any())
    <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif
