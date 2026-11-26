<?php

use App\Actions\SyncGeneration;
use App\Enums\GenerationStatus;
use App\Jobs\PollGeneration;
use App\Models\Generation;
use App\Support\Pixelforge;
use Illuminate\Support\Facades\Http;

function poll(Generation $generation): PollGeneration
{
    $job = (new PollGeneration($generation->fresh()))->withFakeQueueInteractions();
    $job->handle(app(Pixelforge::class), app(SyncGeneration::class));

    return $job;
}

it('settles a generation whose webhooks never arrived', function () {
    $generation = startedGeneration();
    app(SyncGeneration::class)->handle(snapshot($generation, 'queued'));
    Http::fake(['*/v1/generations/*' => Http::response(snapshot($generation, 'succeeded'))]);

    poll($generation)->assertNotReleased();

    expect($generation->fresh()->status)->toBe(GenerationStatus::Succeeded);
});

it('checks again later while the generation is still running', function () {
    $generation = startedGeneration();
    app(SyncGeneration::class)->handle(snapshot($generation, 'queued'));
    Http::fake(['*/v1/generations/*' => Http::response(snapshot($generation, 'processing'))]);

    poll($generation)->assertReleased(delay: 60);
});
