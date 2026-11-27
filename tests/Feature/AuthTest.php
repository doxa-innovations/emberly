<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;

function googleUser(bool $verified = true): GoogleUser
{
    return (new GoogleUser)->setRaw(['email_verified' => $verified])->map([
        'id' => '1234567890', 'name' => 'Ada Lovelace', 'email' => 'Ada@Example.com', 'avatar' => 'https://lh3.googleusercontent.com/a/ada',
    ]);
}

it('signs customers in with Google', function () {
    Socialite::shouldReceive('driver->user')->andReturn(googleUser());

    $this->get('/auth/google/callback')->assertRedirect('/studio');

    expect(User::sole())->email->toBe('ada@example.com')->is_operator->toBeFalse();
    $this->assertAuthenticated();
});

it('refuses unverified Google accounts', function () {
    Socialite::shouldReceive('driver->user')->andReturn(googleUser(verified: false));

    $this->get('/auth/google/callback')->assertForbidden();
    $this->assertGuest();
});

it('signs operators in with a password, and only operators', function () {
    $operator = User::factory()->operator()->create();
    User::factory()->create(['email' => 'customer@example.com', 'password' => 'secret-password']);

    $this->post('/login', ['email' => 'customer@example.com', 'password' => 'secret-password'])->assertSessionHasErrors('email');
    $this->post('/login', ['email' => $operator->email, 'password' => 'secret-password'])->assertRedirect('/admin');
});

it('sends guests to the welcome page', function () {
    $this->get('/studio')->assertRedirect('/');
    $this->get('/')->assertInertia(fn ($page) => $page->component('Welcome'));
});
