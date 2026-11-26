<?php

namespace App\Models;

use App\Enums\GenerationStatus;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'provider_id', 'prompt', 'size', 'quality', 'reference_images', 'status', 'estimated_cost', 'cost',
    'provider_cost', 'usage', 'rates', 'error', 'events', 'image_url', 'image_path', 'started_at', 'completed_at',
])]
class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'reference_images' => 'array',
            'usage' => 'array',
            'rates' => 'array',
            'error' => 'array',
            'events' => 'array',
            'estimated_cost' => 'integer',
            'cost' => 'integer',
            'provider_cost' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
