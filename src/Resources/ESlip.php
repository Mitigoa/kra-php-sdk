<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\DTOs\ESlipResult;

/**
 * Resource for e-Slip verification API endpoints.
 */
class ESlip extends Resource
{
    private const VERIFY_ENDPOINT = '/eslip/verify';

    /**
     * Verify an e-Slip.
     *
     * @param string $prn The Payment Reference Number (PRN)
     * @return ESlipResult
     */
    public function verify(string $prn): ESlipResult
    {
        $response = $this->get(self::VERIFY_ENDPOINT, ['prn' => $prn]);
        return ESlipResult::fromResponse($response);
    }

    /**
     * Check if an e-Slip is valid.
     *
     * @param string $prn The Payment Reference Number (PRN)
     * @return bool
     */
    public function isValid(string $prn): bool
    {
        $result = $this->verify($prn);
        return $result->isValid === true;
    }
}
