<?php

namespace App\Http\Controllers;

use App\Actions\QuoteGeneration;
use App\Support\Pixelforge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class QuoteController
{
    public function __invoke(Request $request, QuoteGeneration $quote): JsonResponse
    {
        $input = $request->validate([
            'characters' => ['required', 'integer', 'between:0,4000'],
            'size' => ['required', Rule::in(Pixelforge::SIZES)],
            'quality' => ['required', Rule::in(Pixelforge::QUALITIES)],
            'references' => ['required', 'integer', 'between:0,4'],
        ]);

        return response()->json($quote->handle(...$input));
    }
}
