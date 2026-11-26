<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'google_id', 'avatar', 'password', 'is_operator'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $attributes = ['avatar' => null, 'is_operator' => false, 'balance' => 0];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_operator' => 'boolean',
            'balance' => 'integer',
        ];
    }

    public function generations(): HasMany
    {
        return $this->hasMany(Generation::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
