<?php

use App\Actions\SyncGeneration;
use App\Enums\GenerationStatus;
use App\Enums\LedgerType;
use App\Jobs\StoreGenerationImage;
use App\Models\Generation;
use App\Support\Money;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;

function deliver(Generation $generation, string $status, string $eventId, int $attempt = 1, array $headers = []): TestResponse
{
    $body = json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => "generation.{$status}",
        'created_at' => '2026-11-04T14:03:29Z',
        'data' => ['object' => snapshot($generation, $status)],
    ]);

    return test()->call('POST', '/webhooks/pixelforge', server: collect([...webhookHeaders($body), 'Pixelforge-Delivery-Attempt' => $attempt, ...$headers])
        ->mapWithKeys(fn ($value, $name) => ['HTTP_'.strtoupper(str_replace('-', '_', $name)) => $value])
        ->put('CONTENT_TYPE', 'application/json')
        ->all(), content: $body);
}

it('charges the exact actual cost plus markup when a generation succeeds', function () {
    $generation = startedGeneration();

    deliver($generation, 'processing', 'evt_1')->assertNoContent();
    deliver($generation, 'succeeded', 'evt_2')->assertNoContent();

    $generation->refresh();
    $charge = Money::withMarkup(Money::fromUsd('0.305510000'));

    expect($generation)
        ->status->toBe(GenerationStatus::Succeeded)
        ->provider_cost->toBe(Money::fromUsd('0.305510000'))
        ->cost->toBe($charge)
        ->and($generation->ledgerEntries()->orderBy('id')->pluck('type')->all())->toBe([LedgerType::Hold, LedgerType::Release, LedgerType::Charge])
        ->and($generation->user->fresh()->balance)->toBe(DOLLAR - $charge);

    Bus::assertDispatched(StoreGenerationImage::class);
});

it('records a duplicated event once', function () {
    $generation = startedGeneration();

    deliver($generation, 'succeeded', 'evt_1')->assertNoContent();
    deliver($generation, 'succeeded', 'evt_1', attempt: 2)->assertNoContent();

    expect($generation->ledgerEntries()->where('type', LedgerType::Charge)->count())->toBe(1)
        ->and($generation->fresh()->events)->toHaveCount(2)
        ->and($generation->user->fresh()->balance)->toBe(DOLLAR - $generation->fresh()->cost);
});

it('never moves a finished generation backwards', function () {
    $generation = startedGeneration();

    deliver($generation, 'succeeded', 'evt_2');
    deliver($generation, 'processing', 'evt_1');
    app(SyncGeneration::class)->handle(snapshot($generation, 'queued'));

    expect($generation->fresh()->status)->toBe(GenerationStatus::Succeeded);
});

it('does not charge for a failed generation', function () {
    $generation = startedGeneration();

    deliver($generation, 'failed', 'evt_1')->assertNoContent();

    expect($generation->fresh())->status->toBe(GenerationStatus::Failed)->cost->toBe(0)
        ->and($generation->user->fresh()->balance)->toBe(DOLLAR);
});

it('rejects forged, stale or unsigned webhooks', function (array $headers) {
    $generation = startedGeneration();

    deliver($generation, 'succeeded', 'evt_1', headers: $headers)->assertStatus(400);

    expect($generation->fresh()->status)->toBe(GenerationStatus::Queued);
})->with([
    'wrong secret' => fn () => ['Pixelforge-Signature' => 't='.now()->timestamp.',v1='.hash_hmac('sha256', 'x', 'nope')],
    'stale timestamp' => fn () => ['Pixelforge-Signature' => 't='.now()->subMinutes(10)->timestamp.',v1=abc'],
    'missing' => fn () => ['Pixelforge-Signature' => ''],
]);

it('acknowledges pings and unknown generations', function () {
    $body = json_encode(['id' => 'evt_ping', 'type' => 'ping', 'data' => ['object' => []]]);

    $this->call('POST', '/webhooks/pixelforge', server: [
        'HTTP_PIXELFORGE_SIGNATURE' => webhookHeaders($body)['Pixelforge-Signature'],
        'CONTENT_TYPE' => 'application/json',
    ], content: $body)->assertNoContent();
});
