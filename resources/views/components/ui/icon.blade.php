@props(['name' => 'settings'])
@php
$paths = [
    'building' => 'M3 21h18M5 21V5l7-3 7 3v16M9 21v-4h6v4M9 7h.01M15 7h.01M9 11h.01M15 11h.01',
    'palette' => 'M12 3a9 9 0 100 18h1a2 2 0 001.7-3.05 1.4 1.4 0 011.2-2.15H18a3 3 0 003-3A9.8 9.8 0 0012 3zM7.5 10h.01M10 6.5h.01M15 7h.01M17.5 11h.01',
    'receipt' => 'M6 3h12v18l-3-2-3 2-3-2-3 2V3zM9 7h6M9 11h6M9 15h3',
    'mail' => 'M3 7l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z',
    'message' => 'M21 11.5a8.4 8.4 0 01-.9 3.8A8.5 8.5 0 0112.5 20a8.4 8.4 0 01-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 01-.9-3.8A8.5 8.5 0 018.7 3.9a8.4 8.4 0 013.8-.9h.5a8.5 8.5 0 018 8v.5zM8 11h8M8 15h4',
    'bell' => 'M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
    'users' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M16 3a4 4 0 010 8M22 21v-2a4 4 0 00-3-3.87M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'shield' => 'M12 3l8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3zM8.5 12l2.5 2.5 4.5-5',
    'layers' => 'M12 3L2 8l10 5 10-5-10-5zM2 12l10 5 10-5M2 16l10 5 10-5',
    'network' => 'M8 3h8v5H8zM3 16h6v5H3zM15 16h6v5h-6zM12 8v4M6 16v-4h12v4',
    'megaphone' => 'M3 10v4h4l11 5V5L7 10H3zM7 14l2 7h4l-3-6M21 9v6',
    'template' => 'M4 4h16v16H4zM4 9h16M9 9v11',
    'activity' => 'M3 12h4l3-8 4 16 3-8h4',
    'alert' => 'M12 3L2 21h20L12 3zM12 9v4M12 17h.01',
    'chevron-right' => 'M9 5l7 7-7 7',
    'chevron-down' => 'M6 9l6 6 6-6',
    'arrow-left' => 'M19 12H5M12 19l-7-7 7-7',
    'arrow-up-right' => 'M7 17L17 7M7 7h10v10',
    'plus' => 'M12 5v14M5 12h14',
    'home' => 'M3 10l9-7 9 7v10a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1V10z',
    'settings' => 'M12 8a4 4 0 100 8 4 4 0 000-8zM9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1 1-3z',
];
@endphp
<svg {{ $attributes->class(['size-5 shrink-0'])->merge(['fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'aria-hidden' => 'true']) }}><path d="{{ $paths[$name] ?? $paths['settings'] }}" stroke-linecap="round" stroke-linejoin="round" /></svg>
