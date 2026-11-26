<?php

use App\Actions\QuoteGeneration;
use App\Models\User;
use App\Support\Money;

it('computes provider cost exactly from usage and rates', function () {
    $usage = ['input_text_tokens' => 12, 'input_image_tokens' => 765, 'output_tokens' => 6450, 'reasoning_tokens' => 3184];
    $rates = ['input_text_tokens' => '5.00', 'input_image_tokens' => '10.00', 'output_tokens' => '40.00', 'reasoning_tokens' => '12.50'];

    expect(Money::toUsd(Money::forUsage($usage, $rates)))->toBe('0.305510000');
});

it('adds the 25% markup and rounds up to the next nanodollar', function () {
    expect(Money::withMarkup(305_510_000))->toBe(381_887_500)
        ->and(Money::withMarkup(1))->toBe(2)
        ->and(Money::withMarkup(0))->toBe(0);
});

it('quotes the documented worst case as the maximum', function () {
    fakePricing();

    $quote = app(QuoteGeneration::class)->handle(characters: 400, size: '1536x1024', quality: 'high', references: 2);

    expect($quote['maximum'])->toBe(Money::withMarkup(Money::fromUsd('0.373875000')))
        ->and($quote['estimate'])->toBe(Money::withMarkup(Money::fromUsd('0.330050000')));
});

it('serves quotes to signed-in users', function () {
    fakePricing();

    $this->actingAs(User::factory()->create())
        ->getJson('/quote?characters=0&size=1024x1024&quality=low&references=0')
        ->assertOk()
        ->assertJson(['estimate' => Money::withMarkup(280 * 40_000 + 250 * 12_500), 'maximum' => Money::withMarkup(280 * 40_000 + 500 * 12_500)]);
});
