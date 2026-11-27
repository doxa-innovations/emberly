<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SignInWithGoogle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

final class GoogleController
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, SignInWithGoogle $signIn): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return to_route('login')->with('toast', 'Google sign-in was cancelled or expired. Please try again.');
        }

        Auth::login($signIn->handle($google), remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('studio'));
    }
}
