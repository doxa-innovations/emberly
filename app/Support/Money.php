<?php

namespace App\Support;

use Illuminate\Support\Number;

final class Money
{
    private const NANOS_PER_DOLLAR = '1000000000';

    private const TOKENS_PER_RATE = 1_000_000;

    public static function fromUsd(string $usd): int
    {
        return (int) bcmul($usd, self::NANOS_PER_DOLLAR, 0);
    }

    public static function toUsd(int $nanos): string
    {
        return bcdiv((string) $nanos, self::NANOS_PER_DOLLAR, 9);
    }

    public static function format(int $nanos): string
    {
        return Number::currency((float) self::toUsd($nanos), 'USD', precision: abs($nanos) >= 1_000_000_000 ? 2 : 4);
    }

    public static function forUsage(array $usage, array $rates): int
    {
        $perRateUnit = collect($rates)->map(fn (string $rate, string $type) => ($usage[$type] ?? 0) * self::fromUsd($rate));

        return intdiv($perRateUnit->sum(), self::TOKENS_PER_RATE);
    }

    public static function withMarkup(int $nanos): int
    {
        $percent = 100 + config('services.pixelforge.markup_percent');

        return intdiv($nanos * $percent + 99, 100);
    }
}
