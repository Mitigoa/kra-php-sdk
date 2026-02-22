<?php

declare(strict_types=1);

namespace KraPHP\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception class for all KRA SDK errors.
 *
 * All exceptions in the KraPHP SDK inherit from this base class,
 * providing a consistent interface for error handling.
 */
class KraException extends Exception
{
    /**
     * Additional error details from the API response.
     */
    protected array $details = [];

    /**
     * HTTP status code associated with the error.
     */
    protected ?int $httpStatusCode = null;

    /**
     * KRA-specific error code from the API response.
     */
    protected ?string $kraErrorCode = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $details = [],
        ?int $httpStatusCode = null,
        ?string $kraErrorCode = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
        $this->httpStatusCode = $httpStatusCode;
        $this->kraErrorCode = $kraErrorCode;
    }

    /**
     * Get additional error details from the API response.
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Get HTTP status code associated with the error.
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get KRA-specific error code from the API response.
     */
    public function getKraErrorCode(): ?string
    {
        return $this->kraErrorCode;
    }

    /**
     * Convert exception to array for logging purposes.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'http_status_code' => $this->httpStatusCode,
            'kra_error_code' => $this->kraErrorCode,
            'details' => $this->details,
        ];
    }
}
