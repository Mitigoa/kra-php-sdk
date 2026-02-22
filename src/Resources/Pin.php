<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\DTOs\PinResult;

/**
 * Resource for PIN validation API endpoints.
 */
class Pin extends Resource
{
    private const VALIDATE_ENDPOINT = '/pin/validate';
    private const CHECK_BY_ID_ENDPOINT = '/pin/check-by-id';

    /**
     * Validate a KRA PIN.
     *
     * @param string $pin The KRA PIN to validate (e.g., "A000000010")
     * @return PinResult
     */
    public function validate(string $pin): PinResult
    {
        $response = $this->get(self::VALIDATE_ENDPOINT, ['pin' => $pin]);
        return PinResult::fromResponse($response);
    }

    /**
     * Validate a PIN by National ID.
     *
     * @param string $idNumber The National ID number
     * @return PinResult
     */
    public function validateById(string $idNumber): PinResult
    {
        $response = $this->get(self::CHECK_BY_ID_ENDPOINT, ['idNumber' => $idNumber]);
        return PinResult::fromResponse($response);
    }

    /**
     * Check if a PIN exists.
     *
     * @param string $pin The KRA PIN to check
     * @return bool
     */
    public function exists(string $pin): bool
    {
        $result = $this->validate($pin);
        return $result->isValid === true;
    }
}
