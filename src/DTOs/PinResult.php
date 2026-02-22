<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * DTO for PIN validation results.
 */
class PinResult extends Dto
{
    /** @var string|null */
    public $kraPin;

    /** @var string|null */
    public $taxpayerName;

    /** @var string|null */
    public $pinStatus;

    /** @var string|null */
    public $taxpayerType;

    /** @var string|null */
    public $registrationDate;

    /** @var string|null */
    public $email;

    /** @var string|null */
    public $phoneNumber;

    /** @var string|null */
    public $address;

    /** @var string|null */
    public $taxObligations;

    /** @var string|null */
    public $idNumber;

    /** @var string|null */
    public $idType;

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
        $instance->pinStatus = self::getString($data, 'pinStatus');
        $instance->taxpayerType = self::getString($data, 'taxpayerType');
        $instance->registrationDate = self::getString($data, 'registrationDate');
        $instance->email = self::getString($data, 'email');
        $instance->phoneNumber = self::getString($data, 'phoneNumber');
        $instance->address = self::getString($data, 'address');
        $instance->taxObligations = self::getString($data, 'taxObligations');
        $instance->idNumber = self::getString($data, 'idNumber');
        $instance->idType = self::getString($data, 'idType');
        $instance->isValid = self::getBool($data, 'isValid');
        $instance->message = self::getString($data, 'message');

        return $instance;
    }

    /**
     * Check if the PIN is active.
     */
    public function isActive(): bool
    {
        return strtoupper($this->pinStatus ?? '') === 'ACTIVE';
    }

    /**
     * Get tax obligations as an array.
     */
    public function getTaxObligationsArray(): array
    {
        if ($this->taxObligations === null) {
            return [];
        }

        if (is_array($this->taxObligations)) {
            return $this->taxObligations;
        }

        return explode(',', $this->taxObligations);
    }
}
