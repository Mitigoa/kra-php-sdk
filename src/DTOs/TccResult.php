<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * DTO for Tax Compliance Certificate (TCC) validation results.
 */
class TccResult extends Dto
{
    /** @var string|null */
    public $kraPin;

    /** @var string|null */
    public $taxpayerName;

    /** @var string|null */
    public $tccNumber;

    /** @var string|null */
    public $status;

    /** @var string|null */
    public $expiryDate;

    /** @var string|null */
    public $issueDate;

    /** @var bool|null */
    public $isValid;

    /** @var string|null */
    public $message;

    /**
     * Create from API response.
     */
    public static function fromResponse(array $data): self
    {
        $instance = new self();
        $instance->kraPin = self::getString($data, 'kraPin');
        $instance->taxpayerName = self::getString($data, 'taxpayerName');
        $instance->tccNumber = self::getString($data, 'tccNumber');
        $instance->status = self::getString($data, 'status');
        $instance->expiryDate = self::getString($data, 'expiryDate');
        $instance->issueDate = self::getString($data, 'issueDate');
        $instance->isValid = self::getBool($data, 'isValid');
        $instance->message = self::getString($data, 'message');

        return $instance;
    }

    /**
     * Check if the TCC is valid.
     */
    public function isValidStatus(): bool
    {
        return strtoupper($this->status ?? '') === 'VALID';
    }

    /**
     * Check if the TCC is expired.
     */
    public function isExpired(): bool
    {
        return strtoupper($this->status ?? '') === 'EXPIRED';
    }

    /**
     * Check if the TCC is revoked.
     */
    public function isRevoked(): bool
    {
        return strtoupper($this->status ?? '') === 'REVOKED';
    }
}
