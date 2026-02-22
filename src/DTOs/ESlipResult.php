<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * DTO for e-Slip verification results.
 */
class ESlipResult extends Dto
{
    /** @var string|null */
    public $prn;

    /** @var bool|null */
    public $isValid;

    /** @var float|null */
    public $amount;

    /** @var string|null */
    public $paymentDate;

    /** @var string|null */
    public $taxType;

    /** @var string|null */
    public $payerPin;

    /** @var string|null */
    public $payerName;

    /** @var string|null */
    public $status;

    /** @var string|null */
    public $message;

    /**
     * Create from API response.
     */
    public static function fromResponse(array $data): self
    {
        $instance = new self();
        $instance->prn = self::getString($data, 'prn');
        $instance->isValid = self::getBool($data, 'isValid');
        $instance->amount = self::getFloat($data, 'amount');
        $instance->paymentDate = self::getString($data, 'paymentDate');
        $instance->taxType = self::getString($data, 'taxType');
        $instance->payerPin = self::getString($data, 'payerPin');
        $instance->payerName = self::getString($data, 'payerName');
        $instance->status = self::getString($data, 'status');
        $instance->message = self::getString($data, 'message');

        return $instance;
    }
}
