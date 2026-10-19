@props(['campaign' => null, 'template' => null])
@php
$campaignContext = request()->routeIs('campaigns.*') || ($template && blank($template->event_key)) || (request()->routeIs('communications.create') && request('kind') === 'campaign');
$items = [['label' => 'Home', 'url' => route('dashboard')], ['label' => $campaignContext ? 'Campaigns' : 'Notifications', 'url' => route($campaignContext ? 'campaigns.index' : 'notifications.index')]];
if ($campaign) {
    if ($campaign->exists) { $items[] = ['label' => $campaign->name, 'url' => route('campaigns.show', $campaign)]; }
    if (!$campaign->exists || request()->routeIs('campaigns.edit')) { $items[] = ['label' => $campaign->exists ? 'Edit campaign' : 'New campaign']; }
} elseif ($template) {
    $items[] = ['label' => $campaignContext ? 'Campaign templates' : 'Templates & rules', 'url' => route($campaignContext ? 'campaigns.templates' : 'communications.index')];
    $items[] = ['label' => $template->exists ? $template->name : 'New template'];
} elseif (request()->routeIs('communications.index', 'campaigns.templates')) {
    $items[] = ['label' => $campaignContext ? 'Campaign templates' : 'Templates & rules'];
} elseif (request()->routeIs('communications.deliveries', 'campaigns.deliveries')) {
    $items[] = ['label' => 'Delivery activity'];
} elseif (request()->routeIs('communications.incidents')) {
    $items[] = ['label' => 'Incidents'];
}
@endphp
<x-ui.breadcrumbs :items="$items" />
@if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
