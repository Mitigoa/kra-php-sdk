<?php

declare(strict_types=1);

namespace KraPHP;

use GuzzleHttp\Client as GuzzleClient;
use KraPHP\Auth\OAuth2Handler;
use KraPHP\Auth\TokenCache;
use KraPHP\Config\KraConfig;
use KraPHP\Exceptions\KraException;
use KraPHP\Http\HttpClient;
use KraPHP\Resources\ESlip;
use KraPHP\Resources\Etims;
use KraPHP\Resources\Excise;
use KraPHP\Resources\Pin;
use KraPHP\Resources\Returns;
use KraPHP\Resources\Tcc;
use KraPHP\Resources\Taxpayer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Main entry point for the KRA GavaConnect PHP SDK.
 *
 * This class provides access to all API resources through fluent interfaces.
 *
 * @example
 * $kra = new KraClient([
 *     'client_id' => 'your-client-id',
 *     'client_secret' => 'your-client-secret',
 *     'environment' => 'sandbox',
 * ]);
 *
 * $pin = $kra->pin()->validate('A000000010');
 * $tcc = $kra->tcc()->validate('A000000010', 'TCC-2024-XXXXX');
 */
class KraClient
{
    private KraConfig $config;
    private HttpClient $httpClient;
    private OAuth2Handler $auth;

    private ?Pin $pin = null;
    private ?Tcc $tcc = null;
    private ?Taxpayer $taxpayer = null;
    private ?Returns $returns = null;
    private ?Etims $etims = null;
    private ?ESlip $eslip = null;
    private ?Excise $excise = null;

    /**
     * Create a new KRA client instance.
     *
     * @param array $config Configuration options
     * @throws \InvalidArgumentException If required configuration is missing
     */
    public function __construct(array $config = [])
    {
        $this->config = new KraConfig($config);
        $this->config->validate();

        $httpClient = $this->createHttpClient();
        $this->httpClient = $httpClient;

        $auth = $this->createAuthHandler();
        $this->auth = $auth;
    }

    /**
     * Get the PIN resource for PIN validation.
     *
     * @return Pin
     */
    public function pin(): Pin
    {
        if ($this->pin === null) {
            $this->pin = new Pin(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->pin;
    }

    /**
     * Get the TCC resource for Tax Compliance Certificate validation.
     *
     * @return Tcc
     */
    public function tcc(): Tcc
    {
        if ($this->tcc === null) {
            $this->tcc = new Tcc(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->tcc;
    }

    /**
     * Get the Taxpayer resource.
     *
     * @return Taxpayer
     */
    public function taxpayer(): Taxpayer
    {
        if ($this->taxpayer === null) {
            $this->taxpayer = new Taxpayer(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->taxpayer;
    }

    /**
     * Get the Returns resource.
     *
     * @return Returns
     */
    public function returns(): Returns
    {
        if ($this->returns === null) {
            $this->returns = new Returns(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->returns;
    }

    /**
     * Get the eTIMS resource.
     *
     * @return Etims
     */
    public function etims(): Etims
    {
        if ($this->etims === null) {
            $this->etims = new Etims(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->etims;
    }

    /**
     * Get the e-Slip resource.
     *
     * @return ESlip
     */
    public function eslip(): ESlip
    {
        if ($this->eslip === null) {
            $this->eslip = new ESlip(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->eslip;
    }

    /**
     * Get the Excise resource.
     *
     * @return Excise
     */
    public function excise(): Excise
    {
        if ($this->excise === null) {
            $this->excise = new Excise(
                $this->config,
                $this->httpClient,
                $this->auth,
                $this->getRequestFactory(),
                $this->getStreamFactory()
            );
        }

        return $this->excise;
    }

    /**
     * Get the configuration instance.
     *
     * @return KraConfig
     */
    public function getConfig(): KraConfig
    {
        return $this->config;
    }

    /**
     * Get the HTTP client instance.
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Get the OAuth2 handler instance.
     *
     * @return OAuth2Handler
     */
    public function getAuth(): OAuth2Handler
    {
        return $this->auth;
    }

    /**
     * Clear all cached tokens.
     */
    public function clearTokens(): void
    {
        $this->auth->clearTokens();
    }

    /**
     * Reset the circuit breaker.
     */
    public function resetCircuitBreaker(): void
    {
        $this->httpClient->resetCircuitBreaker();
    }

    /**
     * Create the HTTP client.
     */
    private function createHttpClient(): HttpClient
    {
        return new HttpClient(
            $this->config,
            $this->getRequestFactory(),
            $this->getStreamFactory()
        );
    }

    /**
     * Create the OAuth2 handler.
     */
    private function createAuthHandler(): OAuth2Handler
    {
        $tokenCache = $this->createTokenCache();

        return new OAuth2Handler(
            $this->config,
            $this->httpClient,
            $this->getRequestFactory(),
            $this->getStreamFactory(),
            $tokenCache
        );
    }

    /**
     * Create the token cache.
     */
    private function createTokenCache(): TokenCache
    {
        $driver = $this->config->getCacheDriver();
        $ttl = $this->config->getCacheTtl();

        switch ($driver) {
            case 'array':
                return new TokenCache(new ArrayAdapter(), 'array', $ttl);

            case 'redis':
                // For Redis, we'd need a Redis connection
                // Using array cache as fallback for now
                return new TokenCache(new ArrayAdapter(), 'array', $ttl);

            case 'memcached':
                // For Memcached, we'd need a Memcached connection
                return new TokenCache(new ArrayAdapter(), 'array', $ttl);

            case 'file':
            default:
                $cacheDir = sys_get_temp_dir() . '/kra-token-cache';
                if (!is_dir($cacheDir)) {
                    mkdir($cacheDir, 0755, true);
                }
                return new TokenCache(
                    new FilesystemAdapter('kra', 0, $cacheDir),
                    'file',
                    $ttl
                );
        }
    }

    /**
     * Get the request factory.
     */
    private function getRequestFactory(): RequestFactoryInterface
    {
        return new GuzzleHttp\Psr7\HttpFactory();
    }

    /**
     * Get the stream factory.
     */
    private function getStreamFactory(): StreamFactoryInterface
    {
        return new GuzzleHttp\Psr7\HttpFactory();
    }
}
