<?php

declare(strict_types=1);

namespace KraPHP\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use KraPHP\Config\KraConfig;
use KraPHP\Exceptions\ApiException;
use KraPHP\Exceptions\AuthException;
use KraPHP\Exceptions\KraException;
use KraPHP\Exceptions\RateLimitException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * PSR-18 HTTP client adapter for KRA API.
 *
 * Wraps Guzzle with retry middleware, circuit breaker,
 * and proper error handling.
 */
final class HttpClient implements ClientInterface
{
    private GuzzleClientInterface $guzzle;
    private KraConfig $config;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private RetryMiddleware $retryMiddleware;
    private CircuitBreaker $circuitBreaker;
    private ?LoggerInterface $logger;

    public function __construct(
        KraConfig $config,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?GuzzleClientInterface $guzzle = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->logger = $logger;
        $this->guzzle = $guzzle ?? $this->createDefaultGuzzleClient();

        $this->retryMiddleware = RetryMiddleware::fromConfig(
            $config->getRetryConfig(),
            $logger
        );

        $this->circuitBreaker = CircuitBreaker::fromConfig(
            $config->getCircuitBreakerConfig(),
            $logger
        );
    }

    /**
     * Send a GET request.
     */
    public function get(string $uri, array $queryParams = [], array $headers = []): ResponseInterface
    {
        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        return $this->request('GET', $uri, $headers);
    }

    /**
     * Send a POST request.
     */
    public function post(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        $body = json_encode($data);
        $headers['Content-Type'] = 'application/json';

        return $this->request('POST', $uri, $headers, $body);
    }

    /**
     * Send a PUT request.
     */
    public function put(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        $body = json_encode($data);
        $headers['Content-Type'] = 'application/json';

        return $this->request('PUT', $uri, $headers, $body);
    }

    /**
     * Send a DELETE request.
     */
    public function delete(string $uri, array $headers = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $headers);
    }

    /**
     * Send an HTTP request.
     *
     * @throws KraException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Check circuit breaker
        if (!$this->circuitBreaker->canExecute()) {
            $this->log('Circuit breaker is open, request blocked');
            throw new KraException('Circuit breaker is open. Too many consecutive failures.');
        }

        try {
            $response = ($this->retryMiddleware)(
                fn () => $this->guzzle->send($this->convertRequest($request)),
                $request
            );

            $this->circuitBreaker->recordSuccess();

            return $this->handleResponse($response, $request);
        } catch (GuzzleException $e) {
            $this->circuitBreaker->recordFailure();
            throw $this->handleGuzzleException($e);
        } catch (KraException $e) {
            $this->circuitBreaker->recordFailure();
            throw $e;
        }
    }

    /**
     * Handle the HTTP response and convert to proper exception if needed.
     *
     * @throws ApiException
     * @throws RateLimitException
     */
    private function handleResponse($response, RequestInterface $request): ResponseInterface
    {
        $statusCode = $response->getStatusCode();
        $responseBody = json_decode((string) $response->getBody(), true) ?? [];

        if ($statusCode === 429) {
            throw RateLimitException::fromHeaders($response->getHeaders());
        }

        if ($statusCode >= 400) {
            throw ApiException::fromResponse($responseBody, $statusCode);
        }

        return $response;
    }

    /**
     * Handle Guzzle exceptions and convert to SDK exceptions.
     */
    private function handleGuzzleException(GuzzleException $e): KraException
    {
        if ($e instanceof \GuzzleHttp\Exception\ClientException) {
            $response = $e->getResponse();
            if ($response) {
                $body = json_decode((string) $response->getBody(), true) ?? [];
                $statusCode = $response->getStatusCode();

                if ($statusCode === 401) {
                    throw new AuthException(
                        $body['error_description'] ?? 'Authentication failed',
                        AuthException::TOKEN_EXPIRED,
                        $e,
                        $body,
                        $statusCode
                    );
                }

                return ApiException::fromResponse($body, $statusCode);
            }
        }

        if ($e instanceof \GuzzleHttp\Exception\ServerException) {
            return new ApiException(
                'Server error: ' . $e->getMessage(),
                ApiException::SERVER_ERROR,
                500,
                $e
            );
        }

        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            return new KraException(
                'Connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return new KraException(
            'HTTP request failed: ' . $e->getMessage(),
            $e->getCode(),
            $e
        );
    }

    /**
     * Create the default Guzzle client.
     */
    private function createDefaultGuzzleClient(): GuzzleClientInterface
    {
        return new GuzzleClient([
            'timeout' => $this->config->getTimeout(),
            'connect_timeout' => 10,
            'verify' => true,
            'force_ip_resolve' => 'v4',
            'handler' => \GuzzleHttp\HandlerStack::create(),
        ]);
    }

    /**
     * Convert PSR-7 request to Guzzle request.
     */
    private function convertRequest(RequestInterface $request): \GuzzleHttp\Psr7\Request
    {
        return new \GuzzleHttp\Psr7\Request(
            $request->getMethod(),
            (string) $request->getUri(),
            $request->getHeaders(),
            (string) $request->getBody()
        );
    }

    /**
     * Log a message.
     */
    private function log(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->debug('KRA SDK HttpClient: ' . $message, $context);
        }
    }

    /**
     * Get the circuit breaker instance.
     */
    public function getCircuitBreaker(): CircuitBreaker
    {
        return $this->circuitBreaker;
    }

    /**
     * Reset the circuit breaker.
     */
    public function resetCircuitBreaker(): void
    {
        $this->circuitBreaker->reset();
    }
}
