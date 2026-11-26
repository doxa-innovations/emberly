<?php

use App\Actions\SyncGeneration;
use App\Enums\GenerationStatus;
use App\Jobs\PollGeneration;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Support\Pixelforge;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

function submit(Generation $generation): SubmitGeneration
{
    $job = (new SubmitGeneration($generation->fresh()))->withFakeQueueInteractions();
    $job->handle(app(Pixelforge::class), app(SyncGeneration::class));

    return $job;
}

it('submits once with an idempotency key and starts the polling fallback', function () {
    $generation = startedGeneration();
    Http::fake(['*/v1/generations' => Http::response(snapshot($generation, 'queued'), 202)]);

    submit($generation)->assertNotReleased();

    expect($generation->fresh())->provider_id->toStartWith('gen_')->status->toBe(GenerationStatus::Queued);
    Http::assertSent(fn (Request $request) => $request->hasHeader('Idempotency-Key', $generation->id)
        && $request['metadata'] === ['generation_id' => $generation->id]
        && $request->hasHeader('Authorization', 'Bearer pf_test'));
    Bus::assertDispatched(PollGeneration::class);
});

it('waits for the Retry-After delay when Pixelforge is at capacity', function (int $status, string $code, int $retryAfter) {
    $generation = startedGeneration();
    Http::fake(['*/v1/generations' => Http::response(['error' => ['type' => 'rate_limit_error', 'code' => $code, 'message' => 'Busy']], $status, ['Retry-After' => (string) $retryAfter])]);

    submit($generation)->assertReleased(delay: $retryAfter);

    expect($generation->fresh()->status)->toBe(GenerationStatus::Queued);
})->with([
    'concurrency limit' => [429, 'concurrency_limit_exceeded', 5],
    'rate limit' => [429, 'rate_limit_exceeded', 12],
    'overloaded' => [503, 'overloaded', 2],
]);

it('retries after a connection failure', function () {
    $generation = startedGeneration();
    Http::fake(['*/v1/generations' => Http::failedConnection()]);

    submit($generation)->assertReleased();
});

it('fails for free when Pixelforge rejects the prompt', function () {
    $generation = startedGeneration();
    $balance = $generation->user->balance;
    Http::fake(['*/v1/generations' => Http::response(['error' => ['type' => 'invalid_request_error', 'code' => 'content_policy_violation', 'message' => 'The prompt was rejected.']], 400)]);

    submit($generation)->assertNotReleased();

    expect($generation->fresh())
        ->status->toBe(GenerationStatus::Failed)
        ->cost->toBe(0)
        ->error->toBe(['code' => 'content_policy_violation', 'message' => 'The prompt was rejected.'])
        ->and($generation->user->fresh()->balance)->toBe(DOLLAR)
        ->and($balance)->toBeLessThan(DOLLAR);
});

it('cancels and refunds when Pixelforge stays busy past the deadline', function () {
    $generation = startedGeneration();

    (new SubmitGeneration($generation))->failed();

    expect($generation->fresh()->status)->toBe(GenerationStatus::Failed)
        ->and($generation->user->fresh()->balance)->toBe(DOLLAR);
});
