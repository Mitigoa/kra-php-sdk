<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * DTO for taxpayer profile data.
 */
class TaxpayerProfile extends Dto
{
    /** @var string|null */
    public $kraPin;

    /** @var string|null */
    public $taxpayerName;

    /** @var string|null */
    public $taxpayerType;

    /** @var string|null */
    public $status;

    /** @var string|null */
    public $registrationDate;

    /** @var string|null */
    public $email;

    /** @var string|null */
    public $phoneNumber;

    /** @var string|null */
    public $address;

    /** @var string|null */
    public $postalCode;

    /** @var string|null */
    public $town;

    /** @var array|null */
    public $obligations;

    /** @var array|null */
    public $liabilities;

    /**
     * Create from API response.
     */
    public static function fromResponse(array $data): self
    {
        $instance = new self();
        $instance->kraPin = self::getString($data, 'kraPin');
        $instance->taxpayerName = self::getString($data, 'taxpayerName');
        $instance->taxpayerType = self::getString($data, 'taxpayerType');
        $instance->status = self::getString($data, 'status');
        $instance->registrationDate = self::getString($data, 'registrationDate');
        $instance->email = self::getString($data, 'email');
        $instance->phoneNumber = self::getString($data, 'phoneNumber');
        $instance->address = self::getString($data, 'address');
        $instance->postalCode = self::getString($data, 'postalCode');
        $instance->town = self::getString($data, 'town');
        $instance->obligations = self::getArray($data, 'obligations');
        $instance->liabilities = self::getArray($data, 'liabilities');

        return $instance;
    }

    /**
     * Check if the taxpayer is active.
     */
    public function isActive(): bool
    {
        return strtoupper($this->status ?? '') === 'ACTIVE';
    }
}
