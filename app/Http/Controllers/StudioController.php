<?php

namespace App\Http\Controllers;

use App\Actions\StartGeneration;
use App\Http\Requests\StartGenerationRequest;
use App\Http\Resources\GenerationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class StudioController
{
    public function index(Request $request): Response
    {
        return Inertia::render('Studio', [
            'generations' => GenerationResource::collection(
                $request->user()->generations()->latest()->latest('id')->paginate(24),
            ),
        ]);
    }

    public function store(StartGenerationRequest $request, StartGeneration $start): RedirectResponse
    {
        $start->handle($request->user(), $request->validated());

        return back();
    }
}
