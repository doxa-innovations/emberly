<?php

namespace App\Actions;

use App\Enums\LedgerType;
use App\Models\LedgerEntry;

final class ConfirmPayment
{
    public function handle(array $payment): LedgerEntry
    {
        return LedgerEntry::createOrFirst(['reference' => $payment['id']], [
            'user_id' => $payment['user_id'],
            'type' => LedgerType::Purchase,
            'amount' => $payment['amount'],
        ]);
    }
}
