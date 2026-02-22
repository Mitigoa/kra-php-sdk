<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\DTOs\TaxpayerProfile;

/**
 * Resource for Taxpayer API endpoints.
 */
class Taxpayer extends Resource
{
    private const OBLIGATIONS_ENDPOINT = '/taxpayer/obligations';
    private const LIABILITIES_ENDPOINT = '/taxpayer/liabilities';

    /**
     * Get tax obligations for a PIN.
     *
     * @param string $pin The KRA PIN
     * @return array
     */
    public function getObligations(string $pin): array
    {
        $response = $this->get(self::OBLIGATIONS_ENDPOINT, ['pin' => $pin]);
        return $response;
    }

    /**
     * Get tax liabilities for a PIN.
     *
     * @param string $pin The KRA PIN
     * @return array
     */
    public function getLiabilities(string $pin): array
    {
        $response = $this->get(self::LIABILITIES_ENDPOINT, ['pin' => $pin]);
        return $response;
    }

    /**
     * Get full taxpayer profile.
     *
     * @param string $pin The KRA PIN
     * @return TaxpayerProfile
     */
    public function getProfile(string $pin): TaxpayerProfile
    {
        // Combine obligations and liabilities
        $obligations = $this->getObligations($pin);
        $liabilities = $this->getLiabilities($pin);

        $data = [
            'kraPin' => $pin,
            'obligations' => $obligations['items'] ?? [],
            'liabilities' => $liabilities['items'] ?? [],
        ];

        return TaxpayerProfile::fromResponse($data);
    }
}
