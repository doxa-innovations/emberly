# Emberly

An image studio on the [Pixelforge](https://pixelforge.promptedit.dev) API, built for the PromptEdit take-home assessment with Laravel 13, Inertia v3 and Vue 3.

**Live:** https://emberly.doxaplc.com · **Webhook:** `https://emberly.doxaplc.com/webhooks/pixelforge`

## What it does

- **Generate.** Prompt, size, quality and up to four reference image URLs. The price is quoted by the server from Pixelforge's live pricing before you submit, with the maximum it can cost.
- **Pay exactly.** You pay Pixelforge's cost plus 25%, computed from the generation's own token usage and rate snapshot. Failed generations are free.
- **Watch it live.** Statuses update while anything is in flight; each image has a receipt (tokens × rate) and a timeline of every webhook delivery, duplicates included.
- **Buy credits.** A demo checkout with an animated card. Credits land only when the simulated processor's signed webhook arrives, exactly once.
- **Operator report.** Charged, Pixelforge cost and profit per UTC day and per user, compared day by day with Pixelforge's `GET /v1/usage`.

## How the money stays right

- All amounts are **integer nanodollars** (1e-9 USD), the precision Pixelforge bills at. No floats anywhere; `app/Support/Money.php` holds all money math.
- A generation **holds** its maximum possible cost, then **releases** the hold and **charges** the exact amount when it finishes, or only releases it when it fails.
- The ledger is **append-only**, and a Postgres trigger keeps each user's balance and every entry's `balance_after`, so reading a balance never sums the ledger, and an update or delete of a ledger row is rejected by the database.
- A webhook delivered twice can't be recorded twice: event ids are recorded, statuses only move forward under a row lock, and `(generation_id, type)` is unique in the ledger.

## How it copes with Pixelforge

- Create requests carry an `Idempotency-Key`, so retries never create a second generation.
- `429` and `503` wait for `Retry-After`; connection failures back off; after 15 minutes the generation is cancelled and refunded.
- A webhook can arrive before the create request returns, or out of order: state is matched by `metadata.generation_id` and never moves backwards.
- If webhooks stop, a delayed job polls Pixelforge until the generation is final.
- Images are copied to storage when they finish, so they outlive Pixelforge's 24-hour URLs.

Use the keyword chips in the studio (`[fail]`, `[slow]`, `[duplicate]`, ...) to trigger these paths on purpose.

## Run it locally

Requirements: PHP 8.4 with `pdo_pgsql` and `bcmath`, Composer, Node 22, PostgreSQL.

```bash
cp .env.example .env            # demo placeholders only, see below
composer install && npm install
php artisan key:generate
php artisan migrate --seed      # creates the operator from OPERATOR_EMAIL / OPERATOR_PASSWORD
npm run build
php artisan serve               # in one terminal
php artisan queue:work          # in another
```

Pixelforge webhooks need a public URL; without one the polling fallback still settles every generation.

Tests run against PostgreSQL (the ledger lives in triggers). Create a database called `emberly_test`, then:

```bash
./vendor/bin/pest
./vendor/bin/pint --test
```

## About the environment values

`.env.example` contains **demo placeholders, not secrets**, so the project runs out of the box. In production nothing sensitive lives in this repository: every secret (database password, app key, Pixelforge key and webhook secret, Google OAuth client, operator password) is stored in Infisical and synced into the Kubernetes cluster at runtime. The CI workflows use no secrets at all.

## Deployment

Pushing to `staging` runs the tests and builds `ghcr.io/doxa-innovations/emberly:staging-<sha>`. Merging the `staging` → `main` pull request rebuilds that exact commit as `prod-<sha>`. Argo CD deploys only the production image, from the Doxa platform repository; there is no staging environment in the cluster. See `.context/architecture.md` for the full picture.
