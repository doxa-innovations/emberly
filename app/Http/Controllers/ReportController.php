<?php

namespace App\Http\Controllers;

use App\Actions\BuildReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController
{
    private const RANGES = [7, 30, 90];

    public function __invoke(Request $request, BuildReport $report): Response
    {
        $input = $request->validate([
            'days' => ['nullable', 'integer', Rule::in(self::RANGES)],
            'user' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $days = (int) ($input['days'] ?? 30);
        $user = isset($input['user']) ? User::find($input['user']) : null;
        $today = CarbonImmutable::today('UTC');

        return Inertia::render('Admin', [
            'ranges' => self::RANGES,
            'days' => $days,
            'user' => $user?->only('id', 'name', 'email'),
            'report' => $report->handle($today->subDays($days - 1), $today, $user),
        ]);
    }
}
