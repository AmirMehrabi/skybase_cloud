@props(['title' => null, 'description' => null])
<x-ui.card :title="$title" :subtitle="$description">{{ $slot }}</x-ui.card>
