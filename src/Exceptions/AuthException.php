<?php

declare(strict_types=1);

namespace KraPHP\Exceptions;

/**
 * Exception thrown when authentication fails.
 *
 * This includes:
 * - Invalid client credentials
 * - Token expired or invalid
 * - OAuth2 flow errors
 * - Token refresh failures
 */
class AuthException extends KraException
{
    /**
     * Error code for invalid credentials.
     */
    public const INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';

    /**
     * Error code for token expiration.
     */
    public const TOKEN_EXPIRED = 'TOKEN_EXPIRED';

    /**
     * Error code for token refresh failure.
     */
    public const TOKEN_REFRESH_FAILED = 'TOKEN_REFRESH_FAILED';

    /**
     * Error code for invalid grant type.
     */
    public const INVALID_GRANT = 'INVALID_GRANT';

    /**
     * Error code for missing credentials.
     */
    public const MISSING_CREDENTIALS = 'MISSING_CREDENTIALS';

    /**
     * Error code for OAuth2 server errors.
     */
    public const OAUTH_SERVER_ERROR = 'OAUTH_SERVER_ERROR';

    public function __construct(
        string $message = 'Authentication failed',
        string $errorCode = self::INVALID_CREDENTIALS,
        ?Throwable $previous = null,
        array $details = [],
        ?int $httpStatusCode = null
    ) {
        parent::__construct(
            $message,
            $this->mapErrorCodeToHttpStatus($errorCode, $httpStatusCode),
            $previous,
            $details,
            $httpStatusCode ?? $this->mapErrorCodeToHttpStatus($errorCode),
            $errorCode
        );
    }

    /**
     * Map OAuth error codes to HTTP status codes.
     */
    private function mapErrorCodeToHttpStatus(string $errorCode, ?int $currentStatus = null): int
    {
        if ($currentStatus !== null) {
            return $currentStatus;
        }

        return match ($errorCode) {
            self::INVALID_CREDENTIALS => 401,
            self::TOKEN_EXPIRED => 401,
            self::TOKEN_REFRESH_FAILED => 401,
            self::INVALID_GRANT => 400,
            self::MISSING_CREDENTIALS => 400,
            self::OAUTH_SERVER_ERROR => 500,
            default => 401,
        };
    }

    /**
     * Check if this is an invalid credentials error.
     */
    public function isInvalidCredentials(): bool
    {
        return $this->kraErrorCode === self::INVALID_CREDENTIALS;
    }

    /**
     * Check if this is a token expired error.
     */
    public function isTokenExpired(): bool
    {
        return $this->kraErrorCode === self::TOKEN_EXPIRED;
    }

    /**
     * Check if this is a token refresh error.
     */
    public function isTokenRefreshFailed(): bool
    {
        return $this->kraErrorCode === self::TOKEN_REFRESH_FAILED;
    }
}
