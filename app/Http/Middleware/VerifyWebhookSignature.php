<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class VerifyWebhookSignature
{
    private const TOLERANCE_SECONDS = 300;

    public function handle(Request $request, Closure $next, string $service): Response
    {
        $secret = (string) config("services.{$service}.webhook_secret");
        $signature = Str::of((string) $request->header(Str::studly($service).'-Signature'))
            ->explode(',')
            ->mapWithKeys(fn (string $part) => [Str::before($part, '=') => Str::after($part, '=')]);
        $timestamp = (int) $signature->get('t');

        abort_unless(
            $secret !== ''
                && hash_equals(hash_hmac('sha256', "{$timestamp}.{$request->getContent()}", $secret), (string) $signature->get('v1'))
                && abs(now()->timestamp - $timestamp) <= self::TOLERANCE_SECONDS,
            400,
            'Invalid webhook signature.',
        );

        return $next($request);
    }
}
