<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as GoogleUser;

final class SignInWithGoogle
{
    public function handle(GoogleUser $google): User
    {
        abort_unless($google->user['email_verified'] ?? false, 403, 'Your Google email address is not verified.');

        return User::updateOrCreate(['email' => Str::lower($google->getEmail())], [
            'name' => $google->getName() ?: $google->getEmail(),
            'google_id' => $google->getId(),
            'avatar' => $google->getAvatar(),
        ]);
    }
}
