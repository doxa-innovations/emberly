<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

final class PixelforgeException extends RuntimeException
{
    private const RETRYABLE = ['rate_limit_exceeded', 'concurrency_limit_exceeded', 'overloaded'];

    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function from(Response $response): self
    {
        return new self(
            $response->json('error.code') ?? "http_{$response->status()}",
            $response->json('error.message') ?? "Pixelforge answered with HTTP {$response->status()}.",
            $response->status(),
            $response->header('Retry-After') === '' ? null : (int) $response->header('Retry-After'),
        );
    }

    public function isRetryable(): bool
    {
        return in_array($this->errorCode, self::RETRYABLE, true) || $this->status >= 500;
    }
}
