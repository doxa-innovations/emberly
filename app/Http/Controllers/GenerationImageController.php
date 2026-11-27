<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class GenerationImageController
{
    public function __invoke(Generation $generation): Response
    {
        Gate::authorize('view', $generation);

        if ($generation->image_path) {
            return Storage::response($generation->image_path, headers: ['Cache-Control' => 'private, max-age=31536000, immutable']);
        }

        abort_unless($generation->image_url, 404);

        return redirect()->away($generation->image_url);
    }
}
