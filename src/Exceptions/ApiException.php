<?php

declare(strict_types=1);

namespace KraPHP\Exceptions;

use Throwable;

/**
 * Exception thrown when the KRA API returns an error response.
 *
 * This includes 4xx client errors and 5xx server errors.
 */
class ApiException extends KraException
{
    /**
     * Common KRA API error codes.
     */
    public const PIN_NOT_FOUND = 'PIN_NOT_FOUND';
    public const INVALID_PIN_FORMAT = 'INVALID_PIN_FORMAT';
    public const TCC_NOT_FOUND = 'TCC_NOT_FOUND';
    public const TCC_EXPIRED = 'TCC_EXPIRED';
    public const INVALID_REQUEST = 'INVALID_REQUEST';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const FORBIDDEN = 'FORBIDDEN';
    public const NOT_FOUND = 'NOT_FOUND';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    public const INVALID_PARAMETER = 'INVALID_PARAMETER';
    public const MISSING_PARAMETER = 'MISSING_PARAMETER';
    public const DUPLICATE_SUBMISSION = 'DUPLICATE_SUBMISSION';

    public function __construct(
        string $message = 'API request failed',
        ?string $kraErrorCode = null,
        ?int $httpStatusCode = null,
        ?Throwable $previous = null,
        array $details = []
    ) {
        parent::__construct(
            $message,
            $httpStatusCode ?? 500,
            $previous,
            $details,
            $httpStatusCode,
            $kraErrorCode
        );
    }

    /**
     * Create an exception from a Guzzle response body.
     */
    public static function fromResponse(array $response, int $httpStatusCode): self
    {
        $message = $response['message'] ?? $response['error'] ?? 'API request failed';
        $kraErrorCode = $response['code'] ?? $response['error_code'] ?? null;
        $details = $response['details'] ?? $response['errors'] ?? [];

        return new self($message, $kraErrorCode, $httpStatusCode, null, $details);
    }

    /**
     * Check if this is a 404 Not Found error.
     */
    public function isNotFound(): bool
    {
        return $this->httpStatusCode === 404;
    }

    /**
     * Check if this is a 400 Bad Request error.
     */
    public function isBadRequest(): bool
    {
        return $this->httpStatusCode === 400;
    }

    /**
     * Check if this is a 401 Unauthorized error.
     */
    public function isUnauthorized(): bool
    {
        return $this->httpStatusCode === 401;
    }

    /**
     * Check if this is a 403 Forbidden error.
     */
    public function isForbidden(): bool
    {
        return $this->httpStatusCode === 403;
    }

    /**
     * Check if this is a 5xx Server Error.
     */
    public function isServerError(): bool
    {
        return $this->httpStatusCode !== null && $this->httpStatusCode >= 500;
    }

    /**
     * Check if this is a PIN not found error.
     */
    public function isPinNotFound(): bool
    {
        return $this->kraErrorCode === self::PIN_NOT_FOUND;
    }

    /**
     * Check if this is a TCC not found error.
     */
    public function isTccNotFound(): bool
    {
        return $this->kraErrorCode === self::TCC_NOT_FOUND;
    }

    /**
     * Check if this is a TCC expired error.
     */
    public function isTccExpired(): bool
    {
        return $this->kraErrorCode === self::TCC_EXPIRED;
    }
}
