<?php

namespace App\Http\Controllers;

use App\Enums\GenerationStatus;
use App\Enums\LedgerType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CreditsController
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Credits', [
            'reserved' => -(int) $user->ledgerEntries()
                ->where('type', LedgerType::Hold)
                ->whereRelation('generation', fn ($query) => $query->whereIn('status', [GenerationStatus::Queued, GenerationStatus::Processing]))
                ->sum('amount'),
            'entries' => $user->ledgerEntries()->with('generation:id,prompt')->latest('id')->paginate(30),
        ]);
    }
}
