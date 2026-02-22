<?php

declare(strict_types=1);

namespace KraPHP\Resources;

/**
 * Resource for Excise license API endpoints.
 */
class Excise extends Resource
{
    private const CHECK_ENDPOINT = '/excise/check';

    /**
     * Check an excise license by number.
     *
     * @param string $licenseNumber The excise license number
     * @return array
     */
    public function check(string $licenseNumber): array
    {
        return $this->get(self::CHECK_ENDPOINT, ['licenseNumber' => $licenseNumber]);
    }

    /**
     * Check if an excise license is valid.
     *
     * @param string $licenseNumber The excise license number
     * @return bool
     */
    public function isValid(string $licenseNumber): bool
    {
        $result = $this->check($licenseNumber);
        return ($result['isValid'] ?? false) === true;
    }
}
