# SkyBase Marketing Design System

## Product and audience

SkyBase is a cloud operations platform for small and growing MikroTik ISPs. It combines customers, subscriptions, router visibility, RADIUS, billing, IPAM, support, work orders, reports, and a customer portal. The homepage serves owner-operators and lean ISP teams who want less manual work without adopting an enterprise OSS/BSS implementation.

Primary job: help a qualified ISP owner understand the product quickly and book a founder-led guided setup. Secondary jobs: inspect pricing, start a free account, or sign in.

## Cultural reference

Use the 37signals principles of calm work, straightforward software, honest scope, direct customer relationships, and intentional simplicity. This is cultural inspiration, not a visual clone. Avoid startup hype, inflated claims, urgency tactics, feature overload, and invented social proof.

## Visual foundation

- Backgrounds: warm cream `#F7F3EA`, paper `#FFFDF8`, and white.
- Primary text: ink `#17211F`; secondary text: slate `#52605D`.
- Brand anchor: dark teal `#0D2F35`; interactive teal `#145A5A`.
- Accent: amber `#F5C542`, reserved for the primary CTA and small highlights.
- Success: emerald `#15803D`, used sparingly for status indicators.
- Headings: Space Grotesk, 600–700, compact tracking.
- Body: Manrope, 400–600, comfortable 1.6 line height.
- Content width: 1120–1200px; prose width: 640–720px.
- Spacing: generous section rhythm, 72–112px desktop and 48–72px mobile.
- Corners: 10–18px. Avoid pill shapes except small statuses.
- Borders: quiet 1px ink/10 or slate-200. Shadows are subtle and only establish hierarchy.
- Do not use gradients, glassmorphism, decorative blur fields, floating cards, or ornamental blobs.

## Page composition

1. Thin, calm navigation with the exact SkyBase logo, essential links, Login, and Book a guided setup.
2. Text-led hero: eyebrow “For small and growing MikroTik ISPs”; headline “Run your ISP. Not your software.”; focused supporting paragraph; guided-setup primary CTA and pricing secondary CTA.
3. A faithful reconstructed product window based on the current English Blade dashboard. Use labels such as Customers, Subscriptions, Router health, IP pool utilization, and Recent activity. Mark the view as representative and do not invent claims or operational totals.
4. A plain capability index, arranged as editorial rows rather than isolated marketing cards: customers/subscriptions, MikroTik/RADIUS, billing/payments, routers/IPAM, tickets/work orders, and customer portal.
5. Short founder note from Abbie Barlowe paired with the approved Ultech Solutions testimonial.
6. Compact Free, Starter, and Growth pricing summary.
7. Guided-setup section with five required fields and one optional message, followed by a concise footer.

## Components and interaction

- Primary button: amber background, dark ink text, 10–12px radius, strong focus ring, no glow.
- Secondary button/link: white or transparent, teal border/text, visible hover and focus states.
- Product window: quiet browser-frame treatment, realistic dashboard grid, no fake animation.
- Form: visible labels, clear required state, inline errors, 44px minimum controls.
- Mobile navigation: Alpine.js disclosure with Escape and outside-click behavior.
- Motion: 150–200ms color/transform transitions; honor reduced-motion preferences; no parallax.

## Content rules

- Plain English, short sentences, and concrete nouns.
- State who SkyBase is for and what it replaces.
- Keep guided setup as the primary action.
- Preserve accurate plan limits: Free $0 up to 40 subscribers, Starter $69 up to 150, Growth $129 up to 300.
- Preserve direct founder access and approved customer proof.
- Never use “revolutionary,” “all-in-one,” “best,” “effortless,” “24/7,” or unverifiable scale claims.
- Use the exact supplied SkyBase logo in every logo position. Never replace it with initials, emoji, generic marks, invented SVGs, or text alone.

## Responsive and accessibility

- Design desktop at 1440px, tablet at 768px, and mobile at 390px.
- Maintain semantic heading order, keyboard access, visible focus, AA contrast, and touch-friendly targets.
- Stack product proof and form layouts on mobile without horizontal scrolling.
