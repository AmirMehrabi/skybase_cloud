@props(['message'])

@php
    $html = \Illuminate\Support\Str::markdown($message->body ?? '', [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
        'renderer' => [
            'soft_break' => "<br>\n",
        ],
    ]);
@endphp

<div {{ $attributes->merge(['class' => 'ticket-message-body text-sm leading-6 text-slate-800']) }}>
    {!! $html !!}
</div>

@once
    @push('styles')
    <style>
        .ticket-message-body p {
            margin: 0 0 0.75rem;
            white-space: pre-wrap;
        }

        .ticket-message-body p:last-child {
            margin-bottom: 0;
        }

        .ticket-message-body strong {
            font-weight: 700;
            color: rgb(15 23 42);
        }

        .ticket-message-body em {
            font-style: italic;
        }

        .ticket-message-body blockquote {
            margin: 0.75rem 0;
            border-left: 3px solid rgb(148 163 184);
            padding-left: 0.875rem;
            color: rgb(71 85 105);
        }

        .ticket-message-body ul,
        .ticket-message-body ol {
            margin: 0.75rem 0;
            padding-left: 1.25rem;
        }

        .ticket-message-body ul {
            list-style: disc;
        }

        .ticket-message-body ol {
            list-style: decimal;
        }
    </style>
    @endpush
@endonce
