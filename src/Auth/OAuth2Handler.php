<?php

declare(strict_types=1);

namespace KraPHP\Auth;

use KraPHP\Config\KraConfig;
use KraPHP\Exceptions\AuthException;
use KraPHP\Exceptions\RateLimitException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * OAuth2 client credentials handler.
 *
 * Handles the OAuth2 client credentials flow for authenticating
 * with the KRA GavaConnect API.
 */
final class OAuth2Handler
{
    private const TOKEN_PATH = '/oauth/token';

    private KraConfig $config;
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private TokenCache $tokenCache;

    /**
     * Token expiration buffer (seconds) to prevent race conditions.
     */
    private const EXPIRATION_BUFFER = 300;

    public function __construct(
        KraConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        TokenCache $tokenCache
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->tokenCache = $tokenCache;
    }

    /**
     * Get a valid access token.
     *
     * Returns cached token if valid, otherwise requests a new one.
     */
    public function getToken(): string
    {
        // Check if we have a valid cached token
        if ($this->tokenCache->hasAccessToken()) {
            return $this->tokenCache->getAccessToken();
        }

        // Request a new token
        return $this->requestNewToken();
    }

    /**
     * Force refresh the token.
     */
    public function refreshToken(): string
    {
        $this->tokenCache->clearAccessToken();
        return $this->requestNewToken();
    }

    /**
     * Request a new access token from the OAuth2 server.
     *
     * @throws AuthException If authentication fails
     * @throws RateLimitException If rate limited
     */
    private function requestNewToken(): string
    {
        $request = $this->requestFactory->createRequest('POST', $this->getTokenUrl())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json');

        $body = http_build_query([
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'grant_type' => 'client_credentials',
        ]);

        $request = $request->withBody($this->streamFactory->createStream($body));

        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode((string) $response->getBody(), true);

            if ($statusCode === 429) {
                throw RateLimitException::fromHeaders($response->getHeaders(), $statusCode);
            }

            if ($statusCode !== 200) {
                $this->handleErrorResponse($responseBody, $statusCode);
            }

            if (!isset($responseBody['access_token'])) {
                throw new AuthException(
                    'Invalid OAuth2 response: missing access_token',
                    AuthException::OAUTH_SERVER_ERROR,
                    null,
                    $responseBody,
                    $statusCode
                );
            }

            $accessToken = $responseBody['access_token'];
            $expiresIn = $responseBody['expires_in'] ?? 3600;

            // Store in cache with buffer to prevent race conditions
            $cacheTtl = $expiresIn - self::EXPIRATION_BUFFER;
            $this->tokenCache->setAccessToken($accessToken, $cacheTtl);

            // Store refresh token if provided
            if (isset($responseBody['refresh_token'])) {
                $this->tokenCache->setRefreshToken($responseBody['refresh_token']);
            }

            return $accessToken;
        } catch (AuthException | RateLimitException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthException(
                'Failed to obtain access token: ' . $e->getMessage(),
                AuthException::OAUTH_SERVER_ERROR,
                $e
            );
        }
    }

    /**
     * Get the OAuth2 token URL.
     */
    private function getTokenUrl(): string
    {
        return $this->config->getBaseUrl() . self::TOKEN_PATH;
    }

    /**
     * Handle OAuth2 error responses.
     *
     * @throws AuthException
     */
    private function handleErrorResponse(array $response, int $statusCode): void
    {
        $error = $response['error'] ?? 'invalid_request';
        $errorDescription = $response['error_description'] ?? 'An error occurred during authentication';

        $errorCode = match ($error) {
            'invalid_client' => AuthException::INVALID_CREDENTIALS,
            'invalid_grant' => AuthException::INVALID_GRANT,
            'invalid_request' => AuthException::MISSING_CREDENTIALS,
            'unauthorized_client' => AuthException::INVALID_CREDENTIALS,
            'unsupported_grant_type' => AuthException::INVALID_GRANT,
            default => AuthException::OAUTH_SERVER_ERROR,
        };

        throw new AuthException(
            $errorDescription,
            $errorCode,
            null,
            $response,
            $statusCode
        );
    }

    /**
     * Get the token cache instance.
     */
    public function getTokenCache(): TokenCache
    {
        return $this->tokenCache;
    }

    /**
     * Clear all cached tokens.
     */
    public function clearTokens(): void
    {
        $this->tokenCache->clear();
    }
}
