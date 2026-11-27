<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\SyncGeneration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class PixelforgeWebhookController
{
    public function __invoke(Request $request, SyncGeneration $sync): Response
    {
        if (Str::startsWith($request->string('type'), 'generation.')) {
            $sync->handle($request->input('data.object'), [
                'id' => $request->input('id'),
                'type' => $request->input('type'),
                'attempt' => (int) $request->header('Pixelforge-Delivery-Attempt', '1'),
            ]);
        }

        return response()->noContent();
    }
}
