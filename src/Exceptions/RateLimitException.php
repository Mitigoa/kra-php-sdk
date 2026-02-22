<?php

declare(strict_types=1);

namespace KraPHP\Exceptions;

use Throwable;

/**
 * Exception thrown when API rate limit is exceeded.
 *
 * This is thrown when the KRA API returns a 429 Too Many Requests response.
 */
class RateLimitException extends KraException
{
    /**
     * Number of seconds until the rate limit resets.
     */
    protected ?int $retryAfter = null;

    /**
     * Maximum number of requests allowed.
     */
    protected ?int $rateLimitMax = null;

    /**
     * Current number of requests used.
     */
    protected ?int $rateLimitUsed = null;

    public function __construct(
        string $message = 'Rate limit exceeded',
        ?int $httpStatusCode = 429,
        ?int $retryAfter = null,
        ?int $rateLimitMax = null,
        ?int $rateLimitUsed = null,
        ?Throwable $previous = null,
        array $details = []
    ) {
        parent::__construct(
            $message,
            $httpStatusCode ?? 429,
            $previous,
            $details,
            $httpStatusCode,
            'RATE_LIMIT_EXCEEDED'
        );

        $this->retryAfter = $retryAfter;
        $this->rateLimitMax = $rateLimitMax;
        $this->rateLimitUsed = $rateLimitUsed;
    }

    /**
     * Create an exception from response headers.
     */
    public static function fromHeaders(array $headers, int $httpStatusCode = 429): self
    {
        $retryAfter = isset($headers['Retry-After'][0]) ? (int) $headers['Retry-After'][0] : null;
        $rateLimitMax = isset($headers['X-RateLimit-Limit'][0]) ? (int) $headers['X-RateLimit-Limit'][0] : null;
        $rateLimitUsed = isset($headers['X-RateLimit-Used'][0]) ? (int) $headers['X-RateLimit-Used'][0] : null;

        return new self(
            'Rate limit exceeded. Please try again later.',
            $httpStatusCode,
            $retryAfter,
            $rateLimitMax,
            $rateLimitUsed
        );
    }

    /**
     * Get the number of seconds until the rate limit resets.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get the maximum number of requests allowed.
     */
    public function getRateLimitMax(): ?int
    {
        return $this->rateLimitMax;
    }

    /**
     * Get the current number of requests used.
     */
    public function getRateLimitUsed(): ?int
    {
        return $this->rateLimitUsed;
    }

    /**
     * Get the remaining number of requests available.
     */
    public function getRateLimitRemaining(): ?int
    {
        if ($this->rateLimitMax !== null && $this->rateLimitUsed !== null) {
            return $this->rateLimitMax - $this->rateLimitUsed;
        }

        return null;
    }

    /**
     * Check if the response includes retry-after information.
     */
    public function hasRetryAfter(): bool
    {
        return $this->retryAfter !== null;
    }

    /**
     * Get the timestamp when the rate limit will reset.
     */
    public function getResetTimestamp(): ?int
    {
        if ($this->retryAfter !== null) {
            return time() + $this->retryAfter;
        }

        return null;
    }
}
