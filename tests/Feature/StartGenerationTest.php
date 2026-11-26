<?php

use App\Enums\GenerationStatus;
use App\Enums\LedgerType;
use App\Jobs\SubmitGeneration;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    fakePricing();
    Bus::fake([SubmitGeneration::class]);
});

$input = ['prompt' => 'A lighthouse at dawn', 'size' => '1024x1024', 'quality' => 'medium'];

it('reserves the maximum possible cost and queues the generation', function () use ($input) {
    $user = User::factory()->withCredits(DOLLAR)->create();

    $this->actingAs($user)->post('/generations', $input)->assertRedirect();

    $generation = Generation::sole();
    $hold = $generation->ledgerEntries()->sole();

    expect($generation->status)->toBe(GenerationStatus::Queued)
        ->and($hold->type)->toBe(LedgerType::Hold)
        ->and($user->fresh()->balance)->toBe(DOLLAR + $hold->amount)
        ->and(-$hold->amount)->toBeGreaterThan($generation->estimated_cost);

    Bus::assertDispatched(SubmitGeneration::class, fn ($job) => $job->generation->is($generation));
});

it('blocks generations the user cannot afford', function () use ($input) {
    $user = User::factory()->withCredits(1_000_000)->create();

    $this->actingAs($user)->post('/generations', $input)->assertSessionHasErrors('prompt');

    expect(Generation::count())->toBe(0)->and($user->fresh()->balance)->toBe(1_000_000);
    Bus::assertNothingDispatched();
});

it('limits how many generations a user can have in flight', function () use ($input) {
    $user = User::factory()->withCredits(10 * DOLLAR)->create();
    Generation::factory()->for($user)->count(4)->create();

    $this->actingAs($user)->post('/generations', $input)->assertSessionHasErrors('prompt');
});

it('validates reference images', function () use ($input) {
    $user = User::factory()->withCredits(DOLLAR)->create();

    $this->actingAs($user)
        ->post('/generations', [...$input, 'reference_images' => ['ftp://example.com/a.png', 'https://a.test/1', 'https://a.test/2', 'https://a.test/3', 'https://a.test/4']])
        ->assertSessionHasErrors(['reference_images', 'reference_images.0']);
});
