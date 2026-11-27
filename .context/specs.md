# Product specification

Emberly is the take-home assessment for PromptEdit (https://pixelforge.promptedit.dev/assessment), built on the Pixelforge API (https://pixelforge.promptedit.dev/docs). Correct money comes before polish.

## Users

- **Customers** sign in with Google, buy demo credits and generate images.
- **Operators** are users with `is_operator`. The seeded operator signs in with email and password (`OPERATOR_EMAIL`, `OPERATOR_PASSWORD`) and can also use the studio.

## Landing page

- `/` is the public landing page for guests: the pitch, the sign-in (Google, plus a hidden operator form), the animated billing receipt, how the money moves, the four statuses, the money guarantees, the test keywords and a short FAQ. Signed-in users are sent to the studio.

## Generating

- The prompt (at most 4,000 characters), size (1024x1024, 1536x1024, 1024x1536), quality (low, medium, high) and up to 4 reference image URLs.
- The estimate is computed on the server (`GET /quote`) from Pixelforge's live pricing and shown before submitting: expected text tokens, exact image and output tokens, midpoint reasoning, plus 25%. The maximum uses +15% text tokens and maximum reasoning.
- Submitting holds the maximum from the balance. Blocked when the balance can't cover it or the user already has 4 generations in flight.
- Test keyword chips (`[succeed]`, `[fail]`, `[slow]`, `[duplicate]`, `[blocked]`, `[overloaded]`) let reviewers trigger Pixelforge's behaviours.

## Pricing

- Users pay Pixelforge's cost plus 25%, computed from the generation's own `usage` and `rates` snapshot, never hardcoded rates.
- Amounts are integer nanodollars: exact for Pixelforge's nine-decimal costs. The markup rounds up to the next nanodollar (it never does at current rates).
- Failed generations cost nothing; their hold is released.

## History

- Live status (queued, processing, succeeded, failed), estimated and actual cost, the image. Polls every 2 s only while something is in flight.
- Images are copied to storage when they finish, so history outlives Pixelforge's 24-hour URLs.
- The detail view shows the token receipt and the timeline, including duplicate webhook deliveries that were ignored.

## Robustness

- Webhooks are verified (HMAC-SHA256 over `timestamp.body`, five-minute tolerance), deduplicated by event id, and can't move a finished generation backwards.
- Submissions use an Idempotency-Key, respect `Retry-After` on 429 and 503, retry connection failures, and give up after 15 minutes with a refund.
- If webhooks never arrive, a delayed job polls the generation with back-off for up to two hours.

## Balances and credits

- An append-only ledger: purchase, hold, release, charge. Every entry records the balance after it.
- "Buy credits" is a demo checkout: an animated card prefilled with test details. The server only receives the amount. A simulated processor confirms by a signed webhook; only that webhook credits the account, exactly once.

## Operator report

- Charged, Pixelforge cost and profit per UTC day (by completion time) and per user, for 7, 30 or 90 days, with a per-user drill-down.
- Each day is compared with Pixelforge's `GET /v1/usage` for this environment's API key.

## Environments

- Production only: `https://emberly.doxaplc.com` on the Doxa K3s platform. The `staging` branch builds an image but nothing is served from it. The Pixelforge key, webhook secret, database and Infisical `prod` environment are production's.
- The Pixelforge webhook URL is `https://emberly.doxaplc.com/webhooks/pixelforge`.
