<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * DTO for NIL return filing result.
 */
class NilReturnResult extends Dto
{
    /** @var string|null */
    public $acknowledgementNumber;

    /** @var string|null */
    public $filedAt;

    /** @var string|null */
    public $pin;

    /** @var string|null */
    public $obligation;

    /** @var string|null */
    public $period;

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
        $instance->acknowledgementNumber = self::getString($data, 'acknowledgementNumber');
        $instance->filedAt = self::getString($data, 'filedAt');
        $instance->pin = self::getString($data, 'pin');
        $instance->obligation = self::getString($data, 'obligation');
        $instance->period = self::getString($data, 'period');
        $instance->status = self::getString($data, 'status');
        $instance->message = self::getString($data, 'message');

        return $instance;
    }

    /**
     * Check if the filing was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->acknowledgementNumber !== null;
    }
}
