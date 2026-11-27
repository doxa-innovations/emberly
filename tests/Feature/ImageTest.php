<?php

use App\Enums\GenerationStatus;
use App\Jobs\StoreGenerationImage;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('keeps a copy of the image after Pixelforge signs it', function () {
    Storage::fake();
    Http::fake(['pixelforge.promptedit.dev/images/*' => Http::response('png-bytes')]);
    $generation = Generation::factory()->create(['status' => GenerationStatus::Succeeded, 'image_url' => 'https://pixelforge.promptedit.dev/images/x.png?signature=abc']);

    (new StoreGenerationImage($generation))->handle();

    expect($generation->fresh()->image_path)->toBe("generations/{$generation->id}.png");
    Storage::assertExists("generations/{$generation->id}.png", 'png-bytes');
});

it('shows images to their owner and operators only', function () {
    Storage::fake();
    $generation = Generation::factory()->create(['status' => GenerationStatus::Succeeded, 'image_path' => 'generations/x.png']);
    Storage::put('generations/x.png', 'png-bytes');

    $this->actingAs($generation->user)->get("/generations/{$generation->id}/image")->assertOk();
    $this->actingAs(User::factory()->operator()->create())->get("/generations/{$generation->id}/image")->assertOk();
    $this->actingAs(User::factory()->create())->get("/generations/{$generation->id}/image")->assertForbidden();
});
