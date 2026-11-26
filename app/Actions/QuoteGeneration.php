<?php

namespace App\Actions;

use App\Support\Money;
use App\Support\Pixelforge;

final class QuoteGeneration
{
    public function __construct(private Pixelforge $pixelforge) {}

    /** @return array{estimate: int, maximum: int} */
    public function handle(int $characters, string $size, string $quality, int $references): array
    {
        ['rates' => $rates, 'tokens' => $tokens] = $this->pixelforge->pricing();

        $textTokens = (int) ceil($characters / $tokens['characters_per_text_token']);
        $reasoning = $tokens['reasoning'][$quality];
        $exact = [
            'input_image_tokens' => $references * $tokens['per_reference_image'],
            'output_tokens' => $tokens['output'][$quality][$size],
        ];

        return [
            'estimate' => $this->price($rates, $exact + [
                'input_text_tokens' => $textTokens,
                'reasoning_tokens' => intdiv($reasoning['min'] + $reasoning['max'], 2),
            ]),
            'maximum' => $this->price($rates, $exact + [
                'input_text_tokens' => (int) ceil($textTokens * (1 + $tokens['text_token_variance'])),
                'reasoning_tokens' => $reasoning['max'],
            ]),
        ];
    }

    private function price(array $rates, array $usage): int
    {
        return Money::withMarkup(Money::forUsage($usage, $rates));
    }
}
