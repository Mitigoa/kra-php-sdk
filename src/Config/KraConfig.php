<?php

declare(strict_types=1);

namespace KraPHP\Config;

/**
 * Configuration value object for KRA SDK.
 *
 * This class ensures that sensitive credentials are scrubbed from
 * debug output and exception messages.
 */
final class KraConfig
{
    /**
     * OAuth2 credentials.
     */
    private string $clientId;
    private string $clientSecret;

    /**
     * Environment setting.
     */
    private string $environment;

    /**
     * Base URLs for different environments.
     */
    private string $sandboxBaseUrl;
    private string $prodBaseUrl;
    private string $etimsSandboxUrl;
    private string $etimsProdUrl;

    /**
     * Cache configuration.
     */
    private string $cacheDriver;
    private int $cacheTtl;

    /**
     * HTTP configuration.
     */
    private int $timeout;
    private int $retryAttempts;

    /**
     * Logging configuration.
     */
    private ?string $logChannel;

    /**
     * eTIMS mTLS configuration.
     */
    private ?string $etimsCertPath;
    private ?string $etimsKeyPath;
    private ?string $etimsCertArn;

    /**
     * Retry configuration.
     */
    private array $retryConfig;

    /**
     * Circuit breaker configuration.
     */
    private array $circuitBreakerConfig;

    public function __construct(array $config = [])
    {
        $this->clientId = $config['client_id'] ?? $_ENV['KRA_CLIENT_ID'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? $_ENV['KRA_CLIENT_SECRET'] ?? '';
        $this->environment = $config['environment'] ?? $_ENV['KRA_ENVIRONMENT'] ?? 'sandbox';

        // Base URLs
        $this->sandboxBaseUrl = $config['sandbox_base_url'] ?? $_ENV['KRA_SANDBOX_BASE_URL'] ?? 'https://api-sandbox.developer.go.ke';
        $this->prodBaseUrl = $config['prod_base_url'] ?? $_ENV['KRA_PROD_BASE_URL'] ?? 'https://api.developer.go.ke';
        $this->etimsSandboxUrl = $config['etims_sandbox_url'] ?? $_ENV['KRA_ETIMS_SANDBOX_URL'] ?? 'https://etims-api-sbx.kra.go.ke';
        $this->etimsProdUrl = $config['etims_prod_url'] ?? $_ENV['KRA_ETIMS_PROD_URL'] ?? 'https://etims-api.kra.go.ke/etims-api';

        // Cache
        $this->cacheDriver = $config['cache_driver'] ?? $_ENV['KRA_CACHE_DRIVER'] ?? 'file';
        $this->cacheTtl = (int) ($config['cache_ttl'] ?? $_ENV['KRA_CACHE_TTL'] ?? 3300);

        // HTTP
        $this->timeout = (int) ($config['timeout'] ?? $_ENV['KRA_TIMEOUT'] ?? 30);
        $this->retryAttempts = (int) ($config['retry_attempts'] ?? $_ENV['KRA_RETRY_ATTEMPTS'] ?? 3);

        // Logging
        $this->logChannel = $config['log_channel'] ?? $_ENV['KRA_LOG_CHANNEL'] ?? null;

        // eTIMS
        $this->etimsCertPath = $config['etims_cert_path'] ?? $_ENV['KRA_ETIMS_CERT_PATH'] ?? null;
        $this->etimsKeyPath = $config['etims_key_path'] ?? $_ENV['KRA_ETIMS_KEY_PATH'] ?? null;
        $this->etimsCertArn = $config['etims_cert_arn'] ?? $_ENV['KRA_ETIMS_CERT_ARN'] ?? null;

        // Retry config
        $this->retryConfig = $config['retry'] ?? [
            'attempts' => 3,
            'base_delay' => 500,
            'max_delay' => 10000,
            'jitter' => 0.20,
            'retry_on' => [429, 500, 502, 503, 504],
        ];

        // Circuit breaker config
        $this->circuitBreakerConfig = $config['circuit_breaker'] ?? [
            'enabled' => true,
            'failure_threshold' => 5,
            'recovery_timeout' => 60,
        ];
    }

    /**
     * Get the OAuth2 client ID.
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Get the OAuth2 client secret.
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * Get the environment setting.
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Check if running in sandbox mode.
     */
    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    /**
     * Check if running in production mode.
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Get the base URL based on environment.
     */
    public function getBaseUrl(): string
    {
        return $this->isSandbox() ? $this->sandboxBaseUrl : $this->prodBaseUrl;
    }

    /**
     * Get the eTIMS base URL based on environment.
     */
    public function getEtimsBaseUrl(): string
    {
        return $this->isSandbox() ? $this->etimsSandboxUrl : $this->etimsProdUrl;
    }

    /**
     * Get the cache driver.
     */
    public function getCacheDriver(): string
    {
        return $this->cacheDriver;
    }

    /**
     * Get the cache TTL in seconds.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Get the HTTP timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the number of retry attempts.
     */
    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    /**
     * Get the log channel.
     */
    public function getLogChannel(): ?string
    {
        return $this->logChannel;
    }

    /**
     * Check if logging is configured.
     */
    public function hasLogChannel(): bool
    {
        return $this->logChannel !== null;
    }

    /**
     * Get the eTIMS certificate path.
     */
    public function getEtimsCertPath(): ?string
    {
        return $this->etimsCertPath;
    }

    /**
     * Get the eTIMS key path.
     */
    public function getEtimsKeyPath(): ?string
    {
        return $this->etimsKeyPath;
    }

    /**
     * Get the eTIMS certificate ARN (for AWS Secrets Manager).
     */
    public function getEtimsCertArn(): ?string
    {
        return $this->etimsCertArn;
    }

    /**
     * Check if eTIMS mTLS is configured.
     */
    public function hasEtimsMtls(): bool
    {
        return $this->etimsCertPath !== null && $this->etimsKeyPath !== null;
    }

    /**
     * Get the retry configuration.
     */
    public function getRetryConfig(): array
    {
        return $this->retryConfig;
    }

    /**
     * Get the circuit breaker configuration.
     */
    public function getCircuitBreakerConfig(): array
    {
        return $this->circuitBreakerConfig;
    }

    /**
     * Validate the configuration.
     *
     * @throws \InvalidArgumentException If required configuration is missing
     */
    public function validate(): void
    {
        if (empty($this->clientId)) {
            throw new \InvalidArgumentException('KRA client_id is required');
        }

        if (empty($this->clientSecret)) {
            throw new \InvalidArgumentException('KRA client_secret is required');
        }

        if (!in_array($this->environment, ['sandbox', 'production'], true)) {
            throw new \InvalidArgumentException('Environment must be either "sandbox" or "production"');
        }

        if (!in_array($this->cacheDriver, ['file', 'redis', 'memcached', 'array'], true)) {
            throw new \InvalidArgumentException('Cache driver must be one of: file, redis, memcached, array');
        }
    }

    /**
     * Debug info - excludes secrets.
     *
     * This method is automatically called by var_dump() and exception handling
     * to ensure credentials are never exposed.
     */
    public function __debugInfo(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => '***REDACTED***',
            'environment' => $this->environment,
            'base_url' => $this->getBaseUrl(),
            'etims_base_url' => $this->getEtimsBaseUrl(),
            'cache_driver' => $this->cacheDriver,
            'cache_ttl' => $this->cacheTtl,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retryAttempts,
            'log_channel' => $this->logChannel,
            'etims_cert_path' => $this->etimsCertPath ? '***SET***' : null,
            'etims_key_path' => $this->etimsKeyPath ? '***SET***' : null,
            'retry_config' => $this->retryConfig,
            'circuit_breaker_config' => $this->circuitBreakerConfig,
        ];
    }

    /**
     * Create a config from environment variables.
     */
    public static function fromEnv(): self
    {
        return new self([]);
    }

    /**
     * Create a config from an array.
     */
    public static function fromArray(array $config): self
    {
        return new self($config);
    }
}
