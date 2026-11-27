<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CreditsController;
use App\Http\Controllers\GenerationImageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/studio', [StudioController::class, 'index'])->name('studio');
    Route::post('/generations', [StudioController::class, 'store'])->middleware('throttle:generations')->name('generations.store');
    Route::get('/generations/{generation}/image', GenerationImageController::class)->name('generations.image');
    Route::get('/quote', QuoteController::class)->middleware('throttle:120,1')->name('quote');
    Route::get('/credits', CreditsController::class)->name('credits');
    Route::get('/credits/checkout', [CheckoutController::class, 'create'])->name('checkout');
    Route::post('/credits/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/admin', ReportController::class)->middleware('can:view-reports')->name('admin');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});
