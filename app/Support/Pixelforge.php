<?php

namespace App\Support;

use App\Exceptions\PixelforgeException;
use App\Models\Generation;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class Pixelforge
{
    public const SIZES = ['1024x1024', '1536x1024', '1024x1536'];

    public const QUALITIES = ['low', 'medium', 'high'];

    public function pricing(): array
    {
        return Cache::remember('pixelforge:pricing', 60, fn () => $this->client()->get('pricing')->throw()->json());
    }

    public function create(Generation $generation): array
    {
        return $this->send(fn (PendingRequest $client) => $client
            ->withHeader('Idempotency-Key', $generation->id)
            ->post('generations', [
                'prompt' => $generation->prompt,
                'size' => $generation->size,
                'quality' => $generation->quality,
                'reference_images' => $generation->reference_images,
                'metadata' => ['generation_id' => $generation->id],
            ]));
    }

    public function find(string $providerId): array
    {
        return $this->send(fn (PendingRequest $client) => $client->get("generations/{$providerId}"));
    }

    public function usage(string $from, string $to): array
    {
        return $this->send(fn (PendingRequest $client) => $client->get('usage', ['start_date' => $from, 'end_date' => $to]));
    }

    private function send(Closure $request): array
    {
        $response = $request($this->client()->withToken((string) config('services.pixelforge.key')));

        if ($response->failed()) {
            throw PixelforgeException::from($response);
        }

        return $response->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.pixelforge.url'))->acceptJson()->connectTimeout(3)->timeout(10);
    }
}
