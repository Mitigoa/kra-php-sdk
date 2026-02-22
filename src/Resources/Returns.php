<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\DTOs\NilReturnResult;

/**
 * Resource for Returns API endpoints.
 */
class Returns extends Resource
{
    private const NIL_ENDPOINT = '/returns/nil';
    private const ITAX_ENDPOINT = '/itax/submit';

    /**
     * File a NIL tax return.
     *
     * @param array $data NIL return data
     * @return NilReturnResult
     */
    public function fileNil(array $data): NilReturnResult
    {
        $response = $this->post(self::NIL_ENDPOINT, $data);
        return NilReturnResult::fromResponse($response);
    }

    /**
     * Submit tax data to iTax.
     *
     * @param array $data Tax data to submit
     * @return array
     */
    public function submitToITax(array $data): array
    {
        return $this->post(self::ITAX_ENDPOINT, $data);
    }
}
