<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * DTO for eTIMS invoice data.
 */
class EtimsInvoice extends Dto
{
    /** @var string|null */
    public $invoiceNumber;

    /** @var string|null */
    public $buyerPin;

    /** @var string|null */
    public $buyerName;

    /** @var string|null */
    public $invoiceDate;

    /** @var string|null */
    public $currency;

    /** @var array|null */
    public $items;

    /** @var float|null */
    public $totalExclVat;

    /** @var float|null */
    public $totalVat;

    /** @var float|null */
    public $totalInclVat;

    /**
     * Create from API response.
     */
    public static function fromResponse(array $data): self
    {
        $instance = new self();
        $instance->invoiceNumber = self::getString($data, 'invoiceNumber');
        $instance->buyerPin = self::getString($data, 'buyerPin');
        $instance->buyerName = self::getString($data, 'buyerName');
        $instance->invoiceDate = self::getString($data, 'invoiceDate');
        $instance->currency = self::getString($data, 'currency');
        $instance->items = self::getArray($data, 'items');
        $instance->totalExclVat = self::getFloat($data, 'totalExclVat');
        $instance->totalVat = self::getFloat($data, 'totalVat');
        $instance->totalInclVat = self::getFloat($data, 'totalInclVat');

        return $instance;
    }

    /**
     * Create from request data.
     */
    public static function fromRequest(array $data): self
    {
        return self::fromResponse($data);
    }

    /**
     * Convert to array for API request.
     */
    public function toRequestArray(): array
    {
        return [
            'invoiceNumber' => $this->invoiceNumber,
            'buyerPin' => $this->buyerPin,
            'buyerName' => $this->buyerName,
            'invoiceDate' => $this->invoiceDate,
            'currency' => $this->currency,
            'items' => $this->items,
            'totalExclVat' => $this->totalExclVat,
            'totalVat' => $this->totalVat,
            'totalInclVat' => $this->totalInclVat,
        ];
    }
}

/**
 * DTO for eTIMS invoice submission response.
 */
class EtimsResponse extends Dto
{
    /** @var string|null */
    public $invoiceId;

    /** @var string|null */
    public $controlUnit;

    /** @var string|null */
    public $qrCode;

    /** @var string|null */
    public $submittedAt;

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
        $instance->invoiceId = self::getString($data, 'invoiceId');
        $instance->controlUnit = self::getString($data, 'controlUnit');
        $instance->qrCode = self::getString($data, 'qrCode');
        $instance->submittedAt = self::getString($data, 'submittedAt');
        $instance->status = self::getString($data, 'status');
        $instance->message = self::getString($data, 'message');

        return $instance;
    }

    /**
     * Check if submission was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->invoiceId !== null;
    }
}
