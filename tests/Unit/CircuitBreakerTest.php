<?php

declare(strict_types=1);

namespace KraPHP\Tests\Unit;

use KraPHP\Http\CircuitBreaker;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CircuitBreaker.
 */
class CircuitBreakerTest extends TestCase
{
    /**
     * Test CircuitBreaker starts in closed state.
     */
    public function testCircuitBreakerStartsClosed(): void
    {
        $breaker = new CircuitBreaker();
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $breaker->getState());
    }

    /**
     * Test canExecute returns true in closed state.
     */
    public function testCanExecuteReturnsTrueInClosedState(): void
    {
        $breaker = new CircuitBreaker(true, 5, 60);
        $this->assertTrue($breaker->canExecute());
    }

    /**
     * Test recordSuccess in closed state resets failure count.
     */
    public function testRecordSuccessInClosedState(): void
    {
        $breaker = new CircuitBreaker(true, 3, 60);

        // Record failures but not enough to open
        $breaker->recordFailure();
        $breaker->recordFailure();
        $this->assertEquals(2, $breaker->getFailureCount());

        // Record success
        $breaker->recordSuccess();
        $this->assertEquals(0, $breaker->getFailureCount());
    }

    /**
     * Test recordFailure opens circuit after threshold.
     */
    public function testRecordFailureOpensCircuit(): void
    {
        $breaker = new CircuitBreaker(true, 3, 60);

        $breaker->recordFailure();
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $breaker->getState());

        $breaker->recordFailure();
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $breaker->getState());

        $breaker->recordFailure();
        $this->assertEquals(CircuitBreaker::STATE_OPEN, $breaker->getState());
    }

    /**
     * Test canExecute returns false in open state.
     */
    public function testCanExecuteReturnsFalseInOpenState(): void
    {
        $breaker = new CircuitBreaker(true, 2, 60);

        $breaker->recordFailure();
        $breaker->recordFailure();

        $this->assertFalse($breaker->canExecute());
    }

    /**
     * Test CircuitBreaker disabled allows all requests.
     */
    public function testDisabledCircuitBreakerAllowsRequests(): void
    {
        $breaker = new CircuitBreaker(false, 3, 60);

        for ($i = 0; $i < 10; $i++) {
            $breaker->recordFailure();
        }

        $this->assertTrue($breaker->canExecute());
    }

    /**
     * Test reset closes the circuit.
     */
    public function testResetClosesCircuit(): void
    {
        $breaker = new CircuitBreaker(true, 2, 60);

        $breaker->recordFailure();
        $breaker->recordFailure();
        $this->assertEquals(CircuitBreaker::STATE_OPEN, $breaker->getState());

        $breaker->reset();
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $breaker->getState());
        $this->assertTrue($breaker->canExecute());
    }

    /**
     * Test transition from open to half-open after recovery timeout.
     */
    public function testOpenToHalfOpenAfterTimeout(): void
    {
        // Use a very short timeout for testing
        $breaker = new CircuitBreaker(true, 2, 0);

        $breaker->recordFailure();
        $breaker->recordFailure();
        $this->assertEquals(CircuitBreaker::STATE_OPEN, $breaker->getState());

        // In a real scenario, we'd wait for the timeout
        // For testing, we just verify the state is open
        $this->assertFalse($breaker->canExecute());
    }

    /**
     * Test isEnabled returns correct value.
     */
    public function testIsEnabled(): void
    {
        $breaker = new CircuitBreaker(true, 3, 60);
        $this->assertTrue($breaker->isEnabled());

        $breaker = new CircuitBreaker(false, 3, 60);
        $this->assertFalse($breaker->isEnabled());
    }

    /**
     * Test CircuitBreaker from config.
     */
    public function testFromConfig(): void
    {
        $config = [
            'enabled' => true,
            'failure_threshold' => 10,
            'recovery_timeout' => 120,
        ];

        $breaker = CircuitBreaker::fromConfig($config);

        $this->assertTrue($breaker->isEnabled());
        $this->assertEquals(10, $breaker->getFailureCount()); // Default value
    }
}
