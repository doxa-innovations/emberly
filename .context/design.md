# Design

Emberly should feel like a darkroom, not an office: the images and the numbers are the only color. Reference: Vercel's Geist system (type, density, crisp borders), applied as a product UI.

## Principles

- **Show the money.** Every cost has a place: estimate before submit, hold while running, exact charge after, receipt on demand. Exact values (nine decimals) are one hover or one click away.
- **State is always visible.** Queued, processing, succeeded and failed each look different at a glance; nothing silently waits.
- **Earned familiarity.** Standard controls, consistent across screens. Delight is saved for moments: an image developing, a payment landing.

## Tokens (`resources/css/app.css`)

- Surfaces: `ink-950` page, `ink-900` panels, `ink-850` raised, `ink-800` selected. Hairlines `line`, input borders `line-strong`. Neutral chroma is 0.
- Text: `fg`, `fg-muted`, `fg-subtle` (all at least 4.5:1 on `ink-950`).
- One accent, `ember` (`oklch(0.75 0.17 52)`), with `ember-ink` text on it. Semantic only: `ok`, `bad`.
- Type: Geist Variable for UI, Geist Mono Variable with tabular numbers for every amount, id and time.
- Radius: 6px controls, 12px panels and images, full for pills and switches.
- Easing: `--ease-out-strong` for enter and press, 150 to 250 ms for UI.

## Controls

- `.btn` with `.btn-primary` (ember), `.btn-secondary` (outlined), `.btn-ghost`. Press scales to 0.97.
- `.field` for every text input, textarea and select: 1px border, 6px radius. Focus turns the border into a 2px white edge; no outer ring, no glow, no blue.
- Booleans are `SwitchField` (native `<input type="checkbox" switch role="switch">`).
- Single choices are `SegmentedControl`: native radios styled as tabs with a sliding thumb.
- Labels sit above inputs, format hints above the input, errors below. No placeholder as label.

## Motion

- The image "develops" (blur and desaturation clear) only when a generation finishes while you watch.
- Processing tiles breathe with a slow ember glow; queued tiles shimmer.
- Balances count to their new value. The checkout card flips to show the CVC and sweeps a sheen while paying.
- No page-load choreography in the app. `prefers-reduced-motion` removes movement and keeps state changes.

## Copy

Plain and specific: say what it costs, what happened and what to do next. No em dashes, no filler verbs, sentence case.
