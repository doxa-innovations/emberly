<?php

use App\Enums\LedgerType;
use App\Models\User;
use Illuminate\Database\QueryException;

it('keeps the balance as the running total of the ledger', function () {
    $user = User::factory()->create();

    $user->ledgerEntries()->create(['type' => LedgerType::Purchase, 'amount' => 10 * DOLLAR, 'reference' => 'pay_1']);
    $entry = $user->ledgerEntries()->create(['type' => LedgerType::Charge, 'amount' => -3 * DOLLAR]);

    expect($user->fresh()->balance)->toBe(7 * DOLLAR)
        ->and($entry->fresh()->balance_after)->toBe(7 * DOLLAR)
        ->and($user->ledgerEntries()->sum('amount'))->toEqual($user->fresh()->balance);
});

it('refuses to change or delete ledger entries', function () {
    $entry = User::factory()->withCredits(DOLLAR)->create()->ledgerEntries()->first();

    expect(fn () => $entry->update(['amount' => 1]))->toThrow(QueryException::class)
        ->and(fn () => $entry->delete())->toThrow(QueryException::class);
});
