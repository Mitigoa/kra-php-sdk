<?php

declare(strict_types=1);

namespace KraPHP\Http;

use Psr\Log\LoggerInterface;

/**
 * Circuit breaker pattern implementation.
 *
 * Halts requests after N consecutive failures to prevent cascading failures.
 */
final class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    private int $failureThreshold;
    private int $recoveryTimeout;
    private bool $enabled;
    private ?LoggerInterface $logger;

    private int $failureCount = 0;
    private int $successCount = 0;
    private string $state = self::STATE_CLOSED;
    private ?int $lastFailureTime = null;

    public function __construct(
        bool $enabled = true,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        ?LoggerInterface $logger = null
    ) {
        $this->enabled = $enabled;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->logger = $logger;
    }

    /**
     * Check if the circuit allows requests.
     */
    public function canExecute(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        switch ($this->state) {
            case self::STATE_CLOSED:
                return true;

            case self::STATE_OPEN:
                // Check if we've passed the recovery timeout
                if ($this->lastFailureTime !== null) {
                    $elapsed = time() - $this->lastFailureTime;
                    if ($elapsed >= $this->recoveryTimeout) {
                        $this->transitionToHalfOpen();
                        return true;
                    }
                }
                return false;

            case self::STATE_HALF_OPEN:
                return true;

            default:
                return true;
        }
    }

    /**
     * Record a successful request.
     */
    public function recordSuccess(): void
    {
        if (!$this->enabled) {
            return;
        }

        switch ($this->state) {
            case self::STATE_CLOSED:
                $this->failureCount = 0;
                break;

            case self::STATE_HALF_OPEN:
                $this->successCount++;
                // After a few successful requests in half-open state, close the circuit
                if ($this->successCount >= 2) {
                    $this->transitionToClosed();
                }
                break;
        }

        $this->log('Success recorded', ['state' => $this->state, 'failures' => $this->failureCount]);
    }

    /**
     * Record a failed request.
     */
    public function recordFailure(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->failureCount++;
        $this->lastFailureTime = time();

        switch ($this->state) {
            case self::STATE_CLOSED:
                if ($this->failureCount >= $this->failureThreshold) {
                    $this->transitionToOpen();
                }
                break;

            case self::STATE_HALF_OPEN:
                // Any failure in half-open state opens the circuit again
                $this->transitionToOpen();
                break;
        }

        $this->log('Failure recorded', ['state' => $this->state, 'failures' => $this->failureCount]);
    }

    /**
     * Get the current state of the circuit breaker.
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Get the number of consecutive failures.
     */
    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * Check if the circuit breaker is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Reset the circuit breaker to initial state.
     */
    public function reset(): void
    {
        $this->failureCount = 0;
        $this->successCount = 0;
        $this->lastFailureTime = null;
        $this->transitionToClosed();
    }

    /**
     * Transition to closed state.
     */
    private function transitionToClosed(): void
    {
        if ($this->state !== self::STATE_CLOSED) {
            $this->state = self::STATE_CLOSED;
            $this->failureCount = 0;
            $this->successCount = 0;
            $this->log('Circuit breaker closed');
        }
    }

    /**
     * Transition to open state.
     */
    private function transitionToOpen(): void
    {
        if ($this->state !== self::STATE_OPEN) {
            $this->state = self::STATE_OPEN;
            $this->successCount = 0;
            $this->log('Circuit breaker opened');
        }
    }

    /**
     * Transition to half-open state.
     */
    private function transitionToHalfOpen(): void
    {
        if ($this->state !== self::STATE_HALF_OPEN) {
            $this->state = self::STATE_HALF_OPEN;
            $this->successCount = 0;
            $this->log('Circuit breaker half-open');
        }
    }

    /**
     * Log a message.
     */
    private function log(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->debug('KRA SDK CircuitBreaker: ' . $message, $context);
        }
    }

    /**
     * Create from configuration array.
     */
    public static function fromConfig(array $config, ?LoggerInterface $logger = null): self
    {
        return new self(
            $config['enabled'] ?? true,
            $config['failure_threshold'] ?? 5,
            $config['recovery_timeout'] ?? 60,
            $logger
        );
    }
}
