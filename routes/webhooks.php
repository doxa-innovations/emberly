<?php

use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Controllers\Webhooks\PixelforgeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/pixelforge', PixelforgeWebhookController::class)->middleware('webhook.signature:pixelforge')->name('webhooks.pixelforge');
Route::post('/payments', PaymentWebhookController::class)->middleware('webhook.signature:payments')->name('webhooks.payments');
