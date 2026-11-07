@extends('layouts.layout')
@section('title', 'API Docs · SkyBase')
@section('meta_description', 'Publish your ISP coverage map on your website or load your published boundaries through the SkyBase GeoJSON API.')
@section('content')
@php
    $candidate = request()->query('isp');
    $slug = is_string($candidate) && preg_match('/\A[a-zA-Z0-9_-]{1,255}\z/', $candidate) ? $candidate : 'YOUR-TENANT-SLUG';
    $feed = route('coverage.public.feed', $slug);
    $map = route('coverage.public.map', $slug);
@endphp
<main class="mx-auto max-w-4xl space-y-10 px-5 py-12 text-slate-900">
    <header><h1 class="text-4xl font-bold">API documentation</h1><p class="mt-3">Add your ISP’s coverage map to a website with an iframe, or use the same published boundaries in your own map.</p></header>
    <nav aria-label="On this page" class="flex flex-wrap gap-4 text-teal-800 underline"><a href="#setup">Publish coverage</a><a href="#embed">Embed the map</a><a href="#feed">Use the API</a><a href="#help">Troubleshooting</a><a href="{{ route('api-docs.legacy') }}">Legacy CRM reference</a></nav>
    <section id="setup" class="space-y-3"><h2 class="text-2xl font-bold">1. Publish your coverage</h2>
        <ol class="list-decimal space-y-2 pl-6"><li>Sign in to your ISP’s SkyBase account. Open <strong>Sites → select a site → Manage coverage</strong>.</li><li>Create an area, draw its boundary, and review its name and availability. Choose <strong>Publish</strong>. The public map uses the last published revision, including while you work on a newer draft.</li><li>As the tenant owner, open <strong>Sites → Website coverage map → Enable public sharing</strong>. This exposes all published coverage for your tenant. Review area names before enabling it.</li><li>Open <strong>Website installation guide</strong> there to get this page with your tenant’s URLs filled in. If you opened this guide from the homepage, replace <code>YOUR-TENANT-SLUG</code> in the examples with your tenant slug.</li></ol>
    </section>
    <section id="embed" class="space-y-3"><h2 class="text-2xl font-bold">2. Embed the interactive map</h2>
        <p>Paste this into the HTML of your coverage page. In WordPress use a Custom HTML block. Preview the page, click a boundary to see its name and availability, then publish your website.</p>
        <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-white"><code>&lt;iframe src="{{ $map }}"
  title="Internet service coverage" width="100%" height="480"
  loading="lazy" style="border:0"&gt;&lt;/iframe&gt;</code></pre>
        @if($slug !== 'YOUR-TENANT-SLUG')<a class="text-teal-800 underline" href="{{ $map }}">Preview your public map</a>@endif
        <p>The map fits your published boundaries automatically. Future edits appear on the next page load after you publish them. Archiving removes an area. Disable public sharing to turn off both the map and feed.</p>
    </section>
    <section id="feed" class="space-y-3"><h2 class="text-2xl font-bold">3. Use coverage in your own application</h2>
        <p>Send a public GET request. No key, cookies or login are required. Browser requests from other origins are supported. Test the URL in a browser or terminal first.</p>
        <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-white"><code>curl --fail '{{ $feed }}'</code></pre>
        <p>A successful response is a GeoJSON FeatureCollection. Each feature has a stable area ID and a Polygon or MultiPolygon with longitude first, latitude second. Keep interior rings when drawing exclusions.</p>
        <pre class="overflow-x-auto rounded-lg bg-slate-100 p-4"><code>{
  "type": "FeatureCollection",
  "features": [{
    "type": "Feature", "id": 12,
    "geometry": {"type":"Polygon","coordinates":[[[51,35],[52,35],[52,36],[51,35]]]},
    "properties": {"name":"North service area","technology":"wireless",
      "availability":"survey_required","evidence":"estimated"}
  }]
}</code></pre>
        <p>Technology is wireless, fiber or other. Availability is available, survey_required, constrained or unavailable. Evidence is declared, estimated or verified, with an optional verified_on date. A boundary indicates mapped coverage. Installation may still require a survey.</p>
        <p>For an existing Leaflet map named <code>map</code>, run this JavaScript after initializing the map. Use the iframe above for a complete map without writing JavaScript.</p>
        <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-white"><code>async function showCoverage(map) {
  const response = await fetch('{{ $feed }}', {credentials: 'omit'});
  if (!response.ok) throw new Error('Coverage unavailable (' + response.status + ')');
  const areas = L.geoJSON(await response.json()).addTo(map);
  if (areas.getBounds().isValid()) map.fitBounds(areas.getBounds());
}
showCoverage(map).catch(console.error);</code></pre>
    </section>
    <section id="help" class="space-y-2"><h2 class="text-2xl font-bold">Troubleshooting</h2><p><strong>404</strong> — Check the slug, account status and public sharing setting.</p><p><strong>200 with an empty features array</strong> — Publish at least one coverage area. Drafts and archived areas are excluded.</p><p><strong>429</strong> — Wait before retrying. The public routes are limited to 120 requests per minute per client IP. Fetch once when opening the page.</p><p><strong>Preview works but the embedded map is missing</strong> — Check that your website builder allows iframe HTML and that your site’s frame-src policy allows the SkyBase host.</p></section>
    <p>Using the older Avinox CRM deployment? Its authentication, customer, subscription, services and lead endpoints are documented in the <a class="text-teal-800 underline" href="{{ route('api-docs.legacy') }}">legacy API reference</a>.</p>
</main>
@endsection
