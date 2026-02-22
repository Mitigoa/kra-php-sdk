<?php

declare(strict_types=1);

namespace KraPHP\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Retry middleware with exponential backoff and jitter.
 *
 * Automatically retries failed requests based on configuration.
 */
final class RetryMiddleware
{
    private int $maxAttempts;
    private int $baseDelayMs;
    private int $maxDelayMs;
    private float $jitter;
    private array $retryOnStatusCodes;
    private ?LoggerInterface $logger;

    public function __construct(
        int $maxAttempts = 3,
        int $baseDelayMs = 500,
        int $maxDelayMs = 10000,
        float $jitter = 0.20,
        array $retryOnStatusCodes = [429, 500, 502, 503, 504],
        ?LoggerInterface $logger = null
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelayMs = $baseDelayMs;
        $this->maxDelayMs = $maxDelayMs;
        $this->jitter = $jitter;
        $this->retryOnStatusCodes = $retryOnStatusCodes;
        $this->logger = $logger;
    }

    /**
     * Execute the request with retry logic.
     *
     * @template T
     * @param \Closure $request The request to execute
     * @return ResponseInterface
     */
    public function __invoke(callable $request, RequestInterface $requestObj): ResponseInterface
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxAttempts) {
            $attempt++;

            try {
                $response = $request();

                // Check if we should retry based on status code
                if ($this->shouldRetry($response)) {
                    $this->logRetry($attempt, $response->getStatusCode(), $requestObj->getUri());

                    if ($attempt < $this->maxAttempts) {
                        $delay = $this->calculateDelay($attempt);
                        usleep($delay * 1000);
                        continue;
                    }
                }

                return $response;
            } catch (\Exception $e) {
                $lastException = $e;

                // Check if we should retry on this exception
                if ($this->shouldRetryOnException($e) && $attempt < $this->maxAttempts) {
                    $this->logException($attempt, $e, $requestObj->getUri());
                    $delay = $this->calculateDelay($attempt);
                    usleep($delay * 1000);
                    continue;
                }

                throw $e;
            }
        }

        // If we get here, we've exhausted retries
        if ($lastException !== null) {
            throw $lastException;
        }

        throw new \RuntimeException('Retry middleware exhausted all attempts');
    }

    /**
     * Determine if the response should trigger a retry.
     */
    private function shouldRetry(ResponseInterface $response): bool
    {
        return in_array($response->getStatusCode(), $this->retryOnStatusCodes, true);
    }

    /**
     * Determine if we should retry on the given exception.
     */
    private function shouldRetryOnException(\Exception $e): bool
    {
        // Retry on connection errors, timeouts, etc.
        return $e instanceof \Psr\Http\Client\NetworkExceptionInterface
            || $e instanceof \GuzzleHttp\Exception\ConnectException
            || $e instanceof \GuzzleHttp\Exception\RequestException;
    }

    /**
     * Calculate the delay with exponential backoff and jitter.
     */
    private function calculateDelay(int $attempt): int
    {
        // Exponential backoff: base_delay * 2^(attempt-1)
        $delay = $this->baseDelayMs * pow(2, $attempt - 1);

        // Cap at max delay
        $delay = min($delay, $this->maxDelayMs);

        // Add jitter: ±20%
        $jitterAmount = $delay * $this->jitter;
        $jitter = random_int((int) -$jitterAmount, (int) $jitterAmount);

        return (int) max(0, $delay + $jitter);
    }

    /**
     * Log retry attempt.
     */
    private function logRetry(int $attempt, int $statusCode, string $uri): void
    {
        if ($this->logger) {
            $this->logger->warning('KRA SDK: Retrying request', [
                'attempt' => $attempt,
                'status_code' => $statusCode,
                'uri' => (string) $uri,
            ]);
        }
    }

    /**
     * Log exception during retry.
     */
    private function logException(int $attempt, \Exception $e, string $uri): void
    {
        if ($this->logger) {
            $this->logger->warning('KRA SDK: Retrying after exception', [
                'attempt' => $attempt,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'uri' => (string) $uri,
            ]);
        }
    }

    /**
     * Create from configuration array.
     */
    public static function fromConfig(array $config, ?LoggerInterface $logger = null): self
    {
        return new self(
            $config['attempts'] ?? 3,
            $config['base_delay'] ?? 500,
            $config['max_delay'] ?? 10000,
            $config['jitter'] ?? 0.20,
            $config['retry_on'] ?? [429, 500, 502, 503, 504],
            $logger
        );
    }
}
