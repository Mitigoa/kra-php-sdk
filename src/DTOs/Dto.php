<?php

declare(strict_types=1);

namespace KraPHP\DTOs;

/**
 * Base DTO class with common functionality.
 *
 * All DTOs use readonly properties (PHP 8.1+) and provide
 * toArray() and toJson() helpers.
 */
abstract class Dto
{
    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convert the DTO to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Get a value from the data array with a fallback.
     */
    protected static function getValue(array $data, string $key, $default = null)
    {
        return $data[$key] ?? $default;
    }

    /**
     * Get a string value from the data array.
     */
    protected static function getString(array $data, string $key, ?string $default = null): ?string
    {
        $value = $data[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * Get an integer value from the data array.
     */
    protected static function getInt(array $data, string $key, ?int $default = null): ?int
    {
        $value = $data[$key] ?? $default;
        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : $default);
    }

    /**
     * Get a float value from the data array.
     */
    protected static function getFloat(array $data, string $key, ?float $default = null): ?float
    {
        $value = $data[$key] ?? $default;
        return is_float($value) ? $value : (is_numeric($value) ? (float) $value : $default);
    }

    /**
     * Get a boolean value from the data array.
     */
    protected static function getBool(array $data, string $key, ?bool $default = null): ?bool
    {
        $value = $data[$key] ?? $default;
        return is_bool($value) ? $value : $default;
    }

    /**
     * Get an array value from the data array.
     */
    protected static function getArray(array $data, string $key, ?array $default = null): ?array
    {
        $value = $data[$key] ?? $default;
        return is_array($value) ? $value : $default;
    }
}
