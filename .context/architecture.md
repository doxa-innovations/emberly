# Architecture

Laravel 13 on Octane (FrankenPHP), Inertia v3 with Vue 3, PostgreSQL 18, the database queue. One image runs two workloads: the web server and `php artisan queue:work`.

## Data (three app tables)

| Table | Holds |
|---|---|
| `users` | Google identity, `is_operator`, optional password (operators), `balance` (nanodollars, maintained by the trigger) |
| `generations` | Request, `status`, `provider_id`, `estimated_cost`, `cost`, `provider_cost`, `usage`, `rates`, `error`, `events` (webhook deliveries), `image_url`, `image_path`, provider timestamps |
| `ledger_entries` | Append-only money movements: `type` (purchase, hold, release, charge), signed `amount`, `balance_after`, `reference` (payment id). Unique `(generation_id, type)` and unique `reference` |

Framework tables: `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`.

Two Postgres triggers (in the ledger migration): before insert, add the amount to `users.balance` and stamp `balance_after`; before update or delete, raise. Reading a balance is one row, however long the ledger.

## Generation lifecycle

1. `StartGeneration`: quote, lock the user row, check balance and in-flight count, create the generation (`queued`) and a `hold` of the maximum, dispatch `SubmitGeneration` after commit.
2. `SubmitGeneration` (job): `POST /v1/generations` with `Idempotency-Key = generation id` and `metadata.generation_id`. 429 and 503 release with `Retry-After`, connection errors back off, other errors fail the generation. Deadline 15 minutes, then fail and refund. On success it dispatches `PollGeneration` a minute later.
3. `SyncGeneration` is the only place state changes. It takes any provider snapshot (create response, webhook, poll, or a local failure), locks the row, records the webhook event, ignores duplicates and backwards moves, and on a final status settles: `release` the hold, then `charge` the exact cost (provider cost from usage and rates, plus 25%). Success dispatches `StoreGenerationImage`.
4. `PollGeneration` (job): if still not final, `GET /v1/generations/{id}` and sync; back off up to two hours.

Duplicates are harmless three times over: event ids recorded on the generation, forward-only statuses under a row lock, and the unique ledger key.

## Payments (simulated processor)

`PurchaseCredits` creates a payment id and queues `DeliverPaymentWebhook`, which POSTs a signed `payment.succeeded` event to `/webhooks/payments`. `ConfirmPayment` writes the `purchase` entry with `createOrFirst` on the unique reference. Card details never reach the server.

## HTTP

- Web routes (`routes/web.php`): guest sign-in, Google OAuth, studio, quote, image proxy (owner or operator), credits, checkout, admin (`can:view-reports`).
- Webhooks (`routes/webhooks.php`, the stateless `api` group under `/webhooks`): `VerifyWebhookSignature:pixelforge` and `:payments`, same scheme with different secrets.

## Code map

- `app/Actions`: `QuoteGeneration`, `StartGeneration`, `SyncGeneration`, `PurchaseCredits`, `ConfirmPayment`, `BuildReport`, `SignInWithGoogle`.
- `app/Jobs`: `SubmitGeneration`, `PollGeneration`, `StoreGenerationImage`, `DeliverPaymentWebhook`.
- `app/Support`: `Pixelforge` (HTTP client), `Money` (all money math).
- `resources/js`: `pages`, `components`, `layouts/AppLayout.vue`, `lib` (money formatting, motion helpers, generation constants).

## Configuration and secrets

Runtime ENV comes from Infisical `prod` `/runtime`: `APP_KEY`, database credentials (`POSTGRES_*`, `DATABASE_URL`, `DB_URL`), `PIXELFORGE_API_KEY`, `PIXELFORGE_WEBHOOK_SECRET`, `PAYMENTS_WEBHOOK_SECRET`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `OPERATOR_EMAIL`, `OPERATOR_PASSWORD`. No build-time values.

## Deployment

- `Dockerfile`: composer and node stages, then `dunglas/frankenphp` running as `www-data`. The entrypoint runs `php artisan optimize`; the default command starts Octane on port 8000. The worker workload passes `queue:work` as args.
- GitHub Actions: `staging.yml` runs Pint and Pest against Postgres, then builds `staging-<sha>` with the shared Doxa workflow; `production.yml` rebuilds the merged PR head as `prod-<sha>`. No secrets are used in CI.
- Platform (doxa-k8s-platform): one production Argo Application with `doxa-app` (web, migrations as an init container), `doxa-app` (worker) and `doxa-postgresql`. A 1 Gi volume on the web pod, shared by the worker, holds images.
