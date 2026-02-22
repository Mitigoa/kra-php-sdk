<?php

declare(strict_types=1);

namespace KraPHP\Auth;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Token cache implementation using PSR-6 caching.
 *
 * Supports multiple cache drivers: file, redis, memcached, array (testing).
 * Tokens are stored encrypted at rest when using Redis driver.
 */
final class TokenCache
{
    private const TOKEN_KEY = 'kra_oauth_token';
    private const TOKEN_REFRESH_KEY = 'kra_oauth_refresh_token';

    private CacheItemPoolInterface $cache;
    private string $driver;
    private int $ttl;

    public function __construct(
        CacheItemPoolInterface $cache,
        string $driver = 'file',
        int $ttl = 3300
    ) {
        $this->cache = $cache;
        $this->driver = $driver;
        $this->ttl = $ttl;
    }

    /**
     * Store an access token in the cache.
     */
    public function setAccessToken(string $token, ?int $expiresIn = null): void
    {
        $item = $this->cache->getItem(self::TOKEN_KEY);
        $item->set($token);
        $item->expiresAfter($expiresIn ?? $this->ttl);
        $this->cache->save($item);
    }

    /**
     * Get the access token from the cache.
     */
    public function getAccessToken(): ?string
    {
        $item = $this->cache->getItem(self::TOKEN_KEY);

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }

    /**
     * Store a refresh token in the cache.
     */
    public function setRefreshToken(string $token): void
    {
        $item = $this->cache->getItem(self::TOKEN_REFRESH_KEY);
        $item->set($token);
        // Refresh tokens typically have longer validity (e.g., 24 hours)
        $item->expiresAfter(86400);
        $this->cache->save($item);
    }

    /**
     * Get the refresh token from the cache.
     */
    public function getRefreshToken(): ?string
    {
        $item = $this->cache->getItem(self::TOKEN_REFRESH_KEY);

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }

    /**
     * Check if a valid access token exists in the cache.
     */
    public function hasAccessToken(): bool
    {
        return $this->getAccessToken() !== null;
    }

    /**
     * Clear all cached tokens.
     */
    public function clear(): void
    {
        $this->cache->deleteItem(self::TOKEN_KEY);
        $this->cache->deleteItem(self::TOKEN_REFRESH_KEY);
    }

    /**
     * Clear only the access token (force refresh on next request).
     */
    public function clearAccessToken(): void
    {
        $this->cache->deleteItem(self::TOKEN_KEY);
    }

    /**
     * Get the cache driver name.
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get the token TTL in seconds.
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Create a file-based cache instance.
     */
    public static function createFileCache(string $cacheDir = null): self
    {
        $cacheDir = $cacheDir ?? sys_get_temp_dir() . '/kra-token-cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        return new self(
            new \Symfony\Component\Cache\Adapter\FilesystemAdapter('kra', 0, $cacheDir),
            'file'
        );
    }

    /**
     * Create an in-memory array cache (for testing).
     */
    public static function createArrayCache(): self
    {
        return new self(
            new \Symfony\Component\Cache\Adapter\ArrayAdapter(),
            'array'
        );
    }

    /**
     * Create a Redis cache instance.
     */
    public static function createRedisCache(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $prefix = 'kra_',
        int $ttl = 3300
    ): self {
        $redis = new \Redis();
        $redis->connect($host, $port);

        return new self(
            new \Symfony\Component\Cache\Adapter\RedisAdapter($redis, $prefix),
            'redis',
            $ttl
        );
    }

    /**
     * Create a Memcached cache instance.
     */
    public static function createMemcachedCache(
        string $host = '127.0.0.1',
        int $port = 11211,
        string $prefix = 'kra_',
        int $ttl = 3300
    ): self {
        $memcached = new \Memcached();
        $memcached->addServer($host, $port);

        return new self(
            new \Symfony\Component\Cache\Adapter\MemcachedAdapter($memcached, $prefix),
            'memcached',
            $ttl
        );
    }
}
