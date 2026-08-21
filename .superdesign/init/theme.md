# Theme

## Compact token summary

- Marketing background: warm cream `#f6f1e8` / `#fbf7ed`
- Primary ink: slate-950
- Brand dark teal: `#0d2f35`
- Accent amber: `#f5c542`
- Success accent: emerald-600/700
- Headings: Space Grotesk, 500–700
- Body: Manrope, 400–600
- Layout: Tailwind default breakpoints; primary content width `max-w-7xl`
- Existing marketing radii: 8px–36px, often overused
- Existing shadows: Tailwind small through custom soft elevated shadows
- Tailwind v4 is configured through CSS with Vite; there is no separate Tailwind config file
- Dark mode is not defined for the public marketing site

## Raw source

Path: `resources/css/app.css`

```css
@import 'tailwindcss';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../../**/*.blade.php';
@source '../**/*.js';
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
}```

The public layout additionally loads Manrope and Space Grotesk from Google Fonts and applies them to body and headings.

