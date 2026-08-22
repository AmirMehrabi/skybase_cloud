@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($routeNames as $routeName)
    <url>
        <loc>{{ route($routeName) }}</loc>
    </url>
@endforeach
</urlset>
