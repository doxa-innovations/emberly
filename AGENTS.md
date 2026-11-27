# Emberly: rules for agents

Emberly is a Laravel 13, Inertia v3 and Vue 3 image studio on the Pixelforge API. Read `.context/specs.md`, `.context/architecture.md` and `.context/design.md` before changing behaviour, structure or UI. When the architecture or the way we work changes, update those files and this one. `CLAUDE.md` and `AGENTS.md` stay identical.

## Backend

- A business operation is an action class in `app/Actions`: one public `handle()`, private methods for its steps. Controllers, jobs and webhooks validate, call an action and respond.
- Use Laravel first: facades, Eloquent, form requests, policies, queued jobs, rate limiters, the HTTP client. Never hand-roll what the framework already does.
- Money is integer nanodollars (1e-9 USD) everywhere, never floats. All money math lives in `App\Support\Money`.
- The ledger is append-only. A Postgres trigger keeps `users.balance` and `ledger_entries.balance_after`; never write balances directly.
- Provider work is idempotent: an Idempotency-Key on create, statuses only move forward, unique ledger keys.
- PostgreSQL only, tests included. Keep the schema lean: add a table only when no existing one fits.
- The app runs under Octane (FrankenPHP): no static or singleton state that outlives a request.

## Frontend

- Pages in `resources/js/pages`, shared pieces in `resources/js/components`, Vue `<script setup>`.
- Follow `.context/design.md`: Geist, near-black surfaces, one ember accent, OKLCH tokens in `resources/css/app.css`.
- Booleans are switches (`SwitchField`), single choices are tabs (`SegmentedControl`), inputs use `.field`: no rings, no glow, focus is a crisp white edge.
- Motion (GSAP or CSS) only explains a change of state, and always honours `prefers-reduced-motion`.
- Follow Google's modern web guidance (`modern-web-guidance` skill) for HTML, CSS and forms.

## Code

- Small, precise code. Names explain intent; a comment only explains a non-obvious why.
- Before committing, review the diff for a shorter, clearer version.
- `./vendor/bin/pint` and `./vendor/bin/pest` must pass (tests need PostgreSQL, see the README).

## Delivery

- Secrets never enter Git, images or logs. Production values come from Infisical; `.env.example` holds demo placeholders only.
- Feature branch, then `staging` (builds `staging-<sha>`), then a PR `staging` into `main` (rebuilds `prod-<same sha>`). Nothing is pushed to `main` directly.
