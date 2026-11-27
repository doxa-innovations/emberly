<?php

namespace App\Jobs;

use App\Models\Generation;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

final class StoreGenerationImage implements ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [5, 30, 120, 600];

    public function __construct(public Generation $generation) {}

    public function handle(): void
    {
        $path = "generations/{$this->generation->id}.png";

        Storage::put($path, Http::timeout(30)->get($this->generation->image_url)->throw()->body());

        $this->generation->update(['image_path' => $path]);
    }
}
