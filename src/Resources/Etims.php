<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\DTOs\EtimsInvoice;
use KraPHP\DTOs\EtimsResponse;

/**
 * Resource for eTIMS API endpoints.
 */
class Etims extends Resource
{
    private const INVOICE_ENDPOINT = '/etims/invoice';
    private const STOCK_ENDPOINT = '/etims/stock';
    private const PURCHASE_ENDPOINT = '/etims/purchase';

    /**
     * Submit an electronic tax invoice.
     *
     * @param EtimsInvoice $invoice The invoice to submit
     * @return EtimsResponse
     */
    public function submitInvoice(EtimsInvoice $invoice): EtimsResponse
    {
        $uri = $this->getEtimsBaseUrl() . self::INVOICE_ENDPOINT;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Add API key for eTIMS
        $headers['X-API-Key'] = $this->config->getClientId();

        $request = $this->createAuthenticatedRequest(
            'POST',
            $uri,
            $headers,
            json_encode($invoice->toRequestArray())
        );

        $response = $this->httpClient->sendRequest($request);
        $data = json_decode((string) $response->getBody(), true) ?? [];

        return EtimsResponse::fromResponse($data);
    }

    /**
     * Submit stock in/out movements.
     *
     * @param array $data Stock movement data
     * @return array
     */
    public function submitStockMovement(array $data): array
    {
        $uri = $this->getEtimsBaseUrl() . self::STOCK_ENDPOINT;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $headers['X-API-Key'] = $this->config->getClientId();

        $request = $this->createAuthenticatedRequest(
            'POST',
            $uri,
            $headers,
            json_encode($data)
        );

        $response = $this->httpClient->sendRequest($request);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Register purchase transactions.
     *
     * @param array $data Purchase data
     * @return array
     */
    public function submitPurchase(array $data): array
    {
        $uri = $this->getEtimsBaseUrl() . self::PURCHASE_ENDPOINT;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $headers['X-API-Key'] = $this->config->getClientId();

        $request = $this->createAuthenticatedRequest(
            'POST',
            $uri,
            $headers,
            json_encode($data)
        );

        $response = $this->httpClient->sendRequest($request);

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}
