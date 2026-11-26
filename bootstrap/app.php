<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/webhooks.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'webhooks',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->alias(['webhook.signature' => VerifyWebhookSignature::class]);
        $middleware->redirectGuestsTo('/');
        $middleware->redirectUsersTo('/studio');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
