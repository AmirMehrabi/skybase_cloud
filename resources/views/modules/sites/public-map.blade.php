<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $tenant->name }} coverage</title>
@vite('resources/js/public-coverage.js')
<style>html,body,main{height:100%;margin:0}body{font:16px system-ui}#status{position:absolute;bottom:1.5rem;left:.75rem;z-index:1000;background:white;padding:.6rem;border-radius:.3rem}</style></head>
<body><main id="map" aria-label="{{ $tenant->name }} coverage" data-feed="{{ route('coverage.public.feed',$tenant->slug) }}" data-tiles="{{ config('coverage.tiles') ?: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' }}" data-attribution="{{ config('coverage.attribution') }}"></main><div role="status" id="status">Loading coverage…</div></body></html>
