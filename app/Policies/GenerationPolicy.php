<?php

namespace App\Policies;

use App\Models\Generation;
use App\Models\User;

final class GenerationPolicy
{
    public function view(User $user, Generation $generation): bool
    {
        return $user->is_operator || $generation->user_id === $user->id;
    }
}
