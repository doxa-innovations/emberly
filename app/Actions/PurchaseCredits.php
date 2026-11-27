<?php

namespace App\Actions;

use App\Jobs\DeliverPaymentWebhook;
use App\Models\User;
use Illuminate\Support\Str;

final class PurchaseCredits
{
    public const PACKS = [5, 10, 25, 50];

    public function handle(User $user, int $dollars): string
    {
        $payment = [
            'id' => 'pay_'.Str::lower(Str::ulid()),
            'user_id' => $user->id,
            'amount' => $dollars * 1_000_000_000,
        ];

        DeliverPaymentWebhook::dispatch($payment)->delay(now()->addSeconds(2));

        return $payment['id'];
    }
}
