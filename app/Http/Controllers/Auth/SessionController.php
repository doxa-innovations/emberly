<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class SessionController
{
    public function create(): Response
    {
        return Inertia::render('Welcome', [
            'googleEnabled' => filled(config('services.google.client_id')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_operator' => true], remember: true)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match an operator account.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
