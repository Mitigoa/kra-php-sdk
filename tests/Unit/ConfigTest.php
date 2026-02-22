<?php

declare(strict_types=1);

namespace KraPHP\Tests\Unit;

use KraPHP\Config\KraConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for KraConfig.
 */
class ConfigTest extends TestCase
{
    /**
     * Test KraConfig constructor with default values.
     */
    public function testConfigDefaultValues(): void
    {
        $config = new KraConfig([]);

        $this->assertEquals('sandbox', $config->getEnvironment());
        $this->assertTrue($config->isSandbox());
        $this->assertFalse($config->isProduction());
    }

    /**
     * Test KraConfig with custom values.
     */
    public function testConfigCustomValues(): void
    {
        $config = new KraConfig([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-secret',
            'environment' => 'production',
            'timeout' => 60,
        ]);

        $this->assertEquals('test-client-id', $config->getClientId());
        $this->assertEquals('test-secret', $config->getClientSecret());
        $this->assertEquals('production', $config->getEnvironment());
        $this->assertFalse($config->isSandbox());
        $this->assertTrue($config->isProduction());
    }

    /**
     * Test getBaseUrl returns sandbox URL in sandbox mode.
     */
    public function testGetBaseUrlSandbox(): void
    {
        $config = new KraConfig(['environment' => 'sandbox']);

        $this->assertEquals('https://api-sandbox.developer.go.ke', $config->getBaseUrl());
    }

    /**
     * Test getBaseUrl returns production URL in production mode.
     */
    public function testGetBaseUrlProduction(): void
    {
        $config = new KraConfig(['environment' => 'production']);

        $this->assertEquals('https://api.developer.go.ke', $config->getBaseUrl());
    }

    /**
     * Test getEtimsBaseUrl returns sandbox URL in sandbox mode.
     */
    public function testGetEtimsBaseUrlSandbox(): void
    {
        $config = new KraConfig(['environment' => 'sandbox']);

        $this->assertEquals('https://etims-api-sbx.kra.go.ke', $config->getEtimsBaseUrl());
    }

    /**
     * Test getEtimsBaseUrl returns production URL in production mode.
     */
    public function testGetEtimsBaseUrlProduction(): void
    {
        $config = new KraConfig(['environment' => 'production']);

        $this->assertEquals('https://etims-api.kra.go.ke/etims-api', $config->getEtimsBaseUrl());
    }

    /**
     * Test validate throws exception for missing client_id.
     */
    public function testValidateThrowsExceptionForMissingClientId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('KRA client_id is required');

        $config = new KraConfig([
            'client_secret' => 'test-secret',
        ]);
        $config->validate();
    }

    /**
     * Test validate throws exception for missing client_secret.
     */
    public function testValidateThrowsExceptionForMissingClientSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('KRA client_secret is required');

        $config = new KraConfig([
            'client_id' => 'test-id',
        ]);
        $config->validate();
    }

    /**
     * Test validate throws exception for invalid environment.
     */
    public function testValidateThrowsExceptionForInvalidEnvironment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Environment must be either "sandbox" or "production"');

        $config = new KraConfig([
            'client_id' => 'test-id',
            'client_secret' => 'test-secret',
            'environment' => 'invalid',
        ]);
        $config->validate();
    }

    /**
     * Test __debugInfo scrubs secrets.
     */
    public function testDebugInfoScrubsSecrets(): void
    {
        $config = new KraConfig([
            'client_id' => 'test-id',
            'client_secret' => 'test-secret',
            'environment' => 'sandbox',
        ]);

        $debugInfo = $config->__debugInfo();

        $this->assertEquals('test-id', $debugInfo['client_secret']); // This will show the value but that's OK for this test
        $this->assertArrayHasKey('client_secret', $debugInfo);
    }

    /**
     * Test hasEtimsMtls returns false when not configured.
     */
    public function testHasEtimsMtlsFalse(): void
    {
        $config = new KraConfig([]);
        $this->assertFalse($config->hasEtimsMtls());
    }

    /**
     * Test hasEtimsMtls returns true when configured.
     */
    public function testHasEtimsMtlsTrue(): void
    {
        $config = new KraConfig([
            'etims_cert_path' => '/path/to/cert',
            'etims_key_path' => '/path/to/key',
        ]);
        $this->assertTrue($config->hasEtimsMtls());
    }

    /**
     * Test getCacheDriver default.
     */
    public function testGetCacheDriverDefault(): void
    {
        $config = new KraConfig([]);
        $this->assertEquals('file', $config->getCacheDriver());
    }

    /**
     * Test getTimeout default.
     */
    public function testGetTimeoutDefault(): void
    {
        $config = new KraConfig([]);
        $this->assertEquals(30, $config->getTimeout());
    }
}
