<?php

declare(strict_types=1);

namespace KraPHP\Tests\Unit;

use KraPHP\Exceptions\ApiException;
use KraPHP\Exceptions\AuthException;
use KraPHP\Exceptions\KraException;
use KraPHP\Exceptions\RateLimitException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Exception classes.
 */
class ExceptionTest extends TestCase
{
    /**
     * Test KraException::toArray.
     */
    public function testKraExceptionToArray(): void
    {
        $exception = new KraException(
            'Test message',
            500,
            null,
            ['key' => 'value'],
            404,
            'ERROR_CODE'
        );

        $array = $exception->toArray();

        $this->assertEquals('Test message', $array['message']);
        $this->assertEquals(500, $array['code']);
        $this->assertEquals(404, $array['http_status_code']);
        $this->assertEquals('ERROR_CODE', $array['kra_error_code']);
        $this->assertEquals(['key' => 'value'], $array['details']);
    }

    /**
     * Test AuthException constants.
     */
    public function testAuthExceptionConstants(): void
    {
        $this->assertEquals('INVALID_CREDENTIALS', AuthException::INVALID_CREDENTIALS);
        $this->assertEquals('TOKEN_EXPIRED', AuthException::TOKEN_EXPIRED);
        $this->assertEquals('TOKEN_REFRESH_FAILED', AuthException::TOKEN_REFRESH_FAILED);
    }

    /**
     * Test AuthException::isInvalidCredentials.
     */
    public function testAuthExceptionIsInvalidCredentials(): void
    {
        $exception = new AuthException(
            'Invalid credentials',
            AuthException::INVALID_CREDENTIALS
        );

        $this->assertTrue($exception->isInvalidCredentials());
        $this->assertFalse($exception->isTokenExpired());
        $this->assertFalse($exception->isTokenRefreshFailed());
    }

    /**
     * Test AuthException::isTokenExpired.
     */
    public function testAuthExceptionIsTokenExpired(): void
    {
        $exception = new AuthException(
            'Token expired',
            AuthException::TOKEN_EXPIRED
        );

        $this->assertTrue($exception->isTokenExpired());
        $this->assertFalse($exception->isInvalidCredentials());
    }

    /**
     * Test ApiException::fromResponse.
     */
    public function testApiExceptionFromResponse(): void
    {
        $response = [
            'message' => 'PIN not found',
            'code' => 'PIN_NOT_FOUND',
            'details' => ['pin' => 'invalid'],
        ];

        $exception = ApiException::fromResponse($response, 404);

        $this->assertEquals('PIN not found', $exception->getMessage());
        $this->assertEquals('PIN_NOT_FOUND', $exception->getKraErrorCode());
        $this->assertEquals(404, $exception->getHttpStatusCode());
        $this->assertTrue($exception->isNotFound());
        $this->assertFalse($exception->isServerError());
    }

    /**
     * Test ApiException::isPinNotFound.
     */
    public function testApiExceptionIsPinNotFound(): void
    {
        $exception = new ApiException(
            'PIN not found',
            ApiException::PIN_NOT_FOUND,
            404
        );

        $this->assertTrue($exception->isPinNotFound());
        $this->assertFalse($exception->isTccNotFound());
    }

    /**
     * Test ApiException::isServerError.
     */
    public function testApiExceptionIsServerError(): void
    {
        $exception = new ApiException(
            'Server error',
            ApiException::SERVER_ERROR,
            500
        );

        $this->assertTrue($exception->isServerError());
        $this->assertFalse($exception->isNotFound());
        $this->assertFalse($exception->isBadRequest());
    }

    /**
     * Test RateLimitException::fromHeaders.
     */
    public function testRateLimitExceptionFromHeaders(): void
    {
        $headers = [
            'Retry-After' => ['60'],
            'X-RateLimit-Limit' => ['100'],
            'X-RateLimit-Used' => ['100'],
        ];

        $exception = RateLimitException::fromHeaders($headers);

        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertEquals(100, $exception->getRateLimitMax());
        $this->assertEquals(100, $exception->getRateLimitUsed());
        $this->assertEquals(0, $exception->getRateLimitRemaining());
        $this->assertTrue($exception->hasRetryAfter());
    }

    /**
     * Test RateLimitException::getResetTimestamp.
     */
    public function testRateLimitExceptionGetResetTimestamp(): void
    {
        $exception = new RateLimitException('Rate limit exceeded', 429, 60);

        $resetTimestamp = $exception->getResetTimestamp();
        $this->assertNotNull($resetTimestamp);
        $this->assertLessThanOrEqual(time() + 70, $resetTimestamp); // Allow small margin
        $this->assertGreaterThanOrEqual(time() + 50, $resetTimestamp);
    }
}
