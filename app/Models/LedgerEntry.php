<?php

namespace App\Models;

use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'generation_id', 'type', 'amount', 'reference'])]
class LedgerEntry extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => LedgerType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}
