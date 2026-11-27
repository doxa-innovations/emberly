<?php

namespace App\Jobs;

use App\Actions\SyncGeneration;
use App\Models\Generation;
use App\Support\Pixelforge;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PollGeneration implements ShouldQueue
{
    use Queueable;

    public function __construct(public Generation $generation) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(2);
    }

    public function handle(Pixelforge $pixelforge, SyncGeneration $sync): void
    {
        if ($this->generation->status->isFinal()) {
            return;
        }

        $snapshot = rescue(fn () => $pixelforge->find($this->generation->provider_id));
        $generation = ($snapshot ? $sync->handle($snapshot) : null) ?? $this->generation;

        if (! $generation->status->isFinal()) {
            $this->release(min(60 * $this->attempts(), 600));
        }
    }
}
