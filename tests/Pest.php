<?php

use App\Actions\StartGeneration;
use App\Jobs\PollGeneration;
use App\Jobs\StoreGenerationImage;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

const DOLLAR = 1_000_000_000;

function fakePricing(): void
{
    Http::fake(['*/v1/pricing' => Http::response([
        'object' => 'pricing',
        'rates' => ['input_text_tokens' => '5.00', 'input_image_tokens' => '10.00', 'output_tokens' => '40.00', 'reasoning_tokens' => '12.50'],
        'tokens' => [
            'characters_per_text_token' => 4,
            'text_token_variance' => 0.15,
            'per_reference_image' => 765,
            'output' => [
                'low' => ['1024x1024' => 280, '1536x1024' => 420, '1024x1536' => 420],
                'medium' => ['1024x1024' => 1100, '1536x1024' => 1650, '1024x1536' => 1650],
                'high' => ['1024x1024' => 4300, '1536x1024' => 6450, '1024x1536' => 6450],
            ],
            'reasoning' => ['low' => ['min' => 0, 'max' => 500], 'medium' => ['min' => 200, 'max' => 2000], 'high' => ['min' => 1000, 'max' => 8000]],
        ],
    ])]);
}

function snapshot(Generation $generation, string $status, array $overrides = []): array
{
    return [
        'id' => 'gen_'.substr(md5($generation->id), 0, 26),
        'object' => 'generation',
        'status' => $status,
        'metadata' => ['generation_id' => $generation->id],
        'rates' => ['input_text_tokens' => '5.00', 'input_image_tokens' => '10.00', 'output_tokens' => '40.00', 'reasoning_tokens' => '12.50'],
        'usage' => $status === 'succeeded'
            ? ['input_text_tokens' => 12, 'input_image_tokens' => 765, 'output_tokens' => 6450, 'reasoning_tokens' => 3184, 'total_tokens' => 10411]
            : null,
        'image_url' => $status === 'succeeded' ? 'https://pixelforge.promptedit.dev/images/x.png?signature=abc' : null,
        'error' => $status === 'failed' ? ['code' => 'generation_failed', 'message' => 'The model could not produce an image.'] : null,
        'created_at' => '2026-11-04T14:03:11Z',
        'started_at' => $status === 'queued' ? null : '2026-11-04T14:03:13Z',
        'completed_at' => in_array($status, ['succeeded', 'failed']) ? '2026-11-04T14:03:29Z' : null,
        ...$overrides,
    ];
}

function webhookHeaders(string $body, string $service = 'Pixelforge', ?int $timestamp = null, ?string $secret = null): array
{
    $timestamp ??= now()->timestamp;
    $secret ??= config('services.'.strtolower($service).'.webhook_secret');

    return [
        "{$service}-Signature" => "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$body}", $secret),
        'Content-Type' => 'application/json',
    ];
}

function startedGeneration(int $credits = DOLLAR): Generation
{
    fakePricing();
    Bus::fake([SubmitGeneration::class, PollGeneration::class, StoreGenerationImage::class]);

    return app(StartGeneration::class)->handle(
        User::factory()->withCredits($credits)->create(),
        ['prompt' => 'A lighthouse at dawn', 'size' => '1536x1024', 'quality' => 'high', 'reference_images' => ['https://example.com/style.png']],
    );
}
