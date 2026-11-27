<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class DeliverPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public array $backoff = [10, 30, 120, 600, 900];

    public function __construct(public array $payment) {}

    public function handle(): void
    {
        $body = json_encode(['id' => 'evt_'.Str::lower(Str::ulid()), 'type' => 'payment.succeeded', 'data' => $this->payment]);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", (string) config('services.payments.webhook_secret'));

        Http::withBody($body)
            ->withHeader('Payments-Signature', "t={$timestamp},v1={$signature}")
            ->timeout(10)
            ->post(config('services.payments.webhook_url'))
            ->throw();
    }
}
