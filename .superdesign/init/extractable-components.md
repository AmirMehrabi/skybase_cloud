# Extractable Components

## Layout Components

The public navigation and footer are embedded in `resources/views/layouts/layout.blade.php`, not standalone Blade components. Do not extract them as DraftComponents for this single-page redesign; pass the full layout as source context.

## Basic Components

### UiTextInput
- Source: `resources/views/components/ui/input/text.blade.php`
- Category: basic
- Description: Shared labeled text input with error and hint states.
- Extractable props: label, name, type, value, placeholder, required, error, hint
- Hardcoded: Tailwind input styles

### UiTextarea
- Source: `resources/views/components/ui/input/textarea.blade.php`
- Category: basic
- Description: Shared labeled textarea with error and hint states.
- Extractable props: label, name, value, placeholder, rows, required, error, hint
- Hardcoded: Tailwind textarea styles

These primitives are intentionally not extracted into Superdesign components because basic controls should remain inline in draft HTML.

