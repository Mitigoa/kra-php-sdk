<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\DTOs\TccResult;

/**
 * Resource for Tax Compliance Certificate (TCC) API endpoints.
 */
class Tcc extends Resource
{
    private const VALIDATE_ENDPOINT = '/tcc/validate';

    /**
     * Validate a Tax Compliance Certificate.
     *
     * @param string $pin The KRA PIN
     * @param string $tccNumber The TCC number
     * @return TccResult
     */
    public function validate(string $pin, string $tccNumber): TccResult
    {
        $response = $this->get(self::VALIDATE_ENDPOINT, [
            'pin' => $pin,
            'tccNumber' => $tccNumber,
        ]);
        return TccResult::fromResponse($response);
    }

    /**
     * Check if a TCC is valid.
     *
     * @param string $pin The KRA PIN
     * @param string $tccNumber The TCC number
     * @return bool
     */
    public function isValid(string $pin, string $tccNumber): bool
    {
        $result = $this->validate($pin, $tccNumber);
        return $result->isValidStatus();
    }
}
