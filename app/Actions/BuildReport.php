<?php

namespace App\Actions;

use App\Enums\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Support\Money;
use App\Support\Pixelforge;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class BuildReport
{
    public function __construct(private Pixelforge $pixelforge) {}

    public function handle(CarbonImmutable $from, CarbonImmutable $to, ?User $user = null): array
    {
        $rows = Generation::query()
            ->where('status', GenerationStatus::Succeeded)
            ->whereBetween('completed_at', [$from->startOfDay(), $to->endOfDay()])
            ->when($user, fn ($query) => $query->whereBelongsTo($user))
            ->selectRaw('completed_at::date as day, user_id, count(*) as generations, sum(cost) as charged, sum(provider_cost) as provider_cost')
            ->groupBy('day', 'user_id')
            ->toBase()
            ->get();

        $billed = $user ? null : $this->billedByPixelforge($from, $to);

        return [
            'days' => $this->days($rows->groupBy('day'), $billed),
            'users' => $this->users($rows->groupBy('user_id')),
            'totals' => $this->sum($rows) + ['pixelforge' => $billed?->sum('cost')],
            'reconciled' => $billed !== null,
        ];
    }

    private function days(Collection $days, ?Collection $billed): Collection
    {
        return $days->keys()
            ->merge($billed?->where('generations', '>', 0)->keys() ?? [])
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn (string $day) => ['day' => $day, ...$this->sum($days->get($day, collect())), 'pixelforge' => $billed?->get($day)['cost'] ?? null]);
    }

    private function users(Collection $users): Collection
    {
        $accounts = User::findMany($users->keys(), ['id', 'name', 'email', 'balance'])->keyBy('id');

        return $users
            ->map(fn (Collection $rows, int $id) => ['user' => $accounts[$id]->only('id', 'name', 'email', 'balance'), ...$this->sum($rows)])
            ->sortByDesc('charged')
            ->values();
    }

    private function sum(Collection $rows): array
    {
        $charged = (int) $rows->sum('charged');
        $providerCost = (int) $rows->sum('provider_cost');

        return [
            'generations' => (int) $rows->sum('generations'),
            'charged' => $charged,
            'provider_cost' => $providerCost,
            'profit' => $charged - $providerCost,
        ];
    }

    private function billedByPixelforge(CarbonImmutable $from, CarbonImmutable $to): ?Collection
    {
        $usage = rescue(fn () => Cache::remember(
            "pixelforge:usage:{$from->toDateString()}:{$to->toDateString()}",
            60,
            fn () => $this->pixelforge->usage($from->toDateString(), $to->toDateString()),
        ));

        return $usage === null ? null : collect($usage['data'])->mapWithKeys(fn (array $day) => [
            $day['date'] => ['generations' => $day['generations'], 'cost' => Money::fromUsd($day['cost_usd'])],
        ]);
    }
}
