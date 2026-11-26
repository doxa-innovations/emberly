<?php

namespace App\Jobs;

use App\Actions\SyncGeneration;
use App\Exceptions\PixelforgeException;
use App\Models\Generation;
use App\Support\Pixelforge;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;

final class SubmitGeneration implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public Generation $generation) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    public function handle(Pixelforge $pixelforge, SyncGeneration $sync): void
    {
        if ($this->generation->provider_id || $this->generation->status->isFinal()) {
            return;
        }

        try {
            $sync->handle($pixelforge->create($this->generation));
            PollGeneration::dispatch($this->generation)->delay(now()->addMinute());
        } catch (PixelforgeException $e) {
            $e->isRetryable()
                ? $this->release($e->retryAfter ?? $this->backoffSeconds())
                : $this->markFailed($e->errorCode, $e->getMessage());
        } catch (ConnectionException) {
            $this->release($this->backoffSeconds());
        }
    }

    public function failed(): void
    {
        $this->markFailed('pixelforge_unavailable', 'Pixelforge stayed too busy to accept this generation, so it was cancelled. You were not charged.');
    }

    private function markFailed(string $code, string $message): void
    {
        app(SyncGeneration::class)->handle([
            'id' => null,
            'status' => 'failed',
            'error' => ['code' => $code, 'message' => $message],
            'metadata' => ['generation_id' => $this->generation->id],
        ]);
    }

    private function backoffSeconds(): int
    {
        return min(2 ** $this->attempts(), 60);
    }
}
