<?php

declare(strict_types=1);

namespace KraPHP\Resources;

use KraPHP\Auth\OAuth2Handler;
use KraPHP\Config\KraConfig;
use KraPHP\Http\HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Base resource class for API endpoints.
 */
abstract class Resource
{
    protected KraConfig $config;
    protected HttpClient $httpClient;
    protected OAuth2Handler $auth;
    protected RequestFactoryInterface $requestFactory;
    protected StreamFactoryInterface $streamFactory;

    public function __construct(
        KraConfig $config,
        HttpClient $httpClient,
        OAuth2Handler $auth,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->auth = $auth;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Get the base URL for API requests.
     */
    protected function getBaseUrl(): string
    {
        return $this->config->getBaseUrl();
    }

    /**
     * Get the eTIMS base URL.
     */
    protected function getEtimsBaseUrl(): string
    {
        return $this->config->getEtimsBaseUrl();
    }

    /**
     * Create an authenticated request.
     */
    protected function createAuthenticatedRequest(
        string $method,
        string $uri,
        array $headers = [],
        ?string $body = null
    ): \Psr\Http\Message\RequestInterface {
        $token = $this->auth->getToken();

        $headers['Authorization'] = 'Bearer ' . $token;
        $headers['Accept'] = 'application/json';

        $request = $this->requestFactory->createRequest($method, $uri);

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if ($body !== null) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        return $request;
    }

    /**
     * Make an authenticated GET request.
     */
    protected function get(string $endpoint, array $queryParams = []): array
    {
        $uri = $this->getBaseUrl() . $endpoint;

        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        $request = $this->createAuthenticatedRequest('GET', $uri);
        $response = $this->httpClient->sendRequest($request);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Make an authenticated POST request.
     */
    protected function post(string $endpoint, array $data = []): array
    {
        $uri = $this->getBaseUrl() . $endpoint;
        $body = json_encode($data);

        $request = $this->createAuthenticatedRequest(
            'POST',
            $uri,
            ['Content-Type' => 'application/json'],
            $body
        );

        $response = $this->httpClient->sendRequest($request);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Make an authenticated PUT request.
     */
    protected function put(string $endpoint, array $data = []): array
    {
        $uri = $this->getBaseUrl() . $endpoint;
        $body = json_encode($data);

        $request = $this->createAuthenticatedRequest(
            'PUT',
            $uri,
            ['Content-Type' => 'application/json'],
            $body
        );

        $response = $this->httpClient->sendRequest($request);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * Make an authenticated DELETE request.
     */
    protected function delete(string $endpoint): array
    {
        $uri = $this->getBaseUrl() . $endpoint;

        $request = $this->createAuthenticatedRequest('DELETE', $uri);
        $response = $this->httpClient->sendRequest($request);

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}
