@props(['items' => []])
<nav aria-label="Breadcrumb" class="mb-5">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-slate-500">
        @foreach($items as $item)
            <li class="flex min-w-0 items-center gap-2">
                @unless($loop->first)<x-ui.icon name="chevron-right" class="size-3 text-slate-300" />@endunless
                @if(!$loop->last && !empty($item['url']))<a href="{{ $item['url'] }}" class="rounded transition hover:text-teal-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal-700">{{ $item['label'] }}</a>
                @else<span aria-current="page" class="truncate text-slate-800">{{ $item['label'] }}</span>@endif
            </li>
        @endforeach
    </ol>
</nav>
