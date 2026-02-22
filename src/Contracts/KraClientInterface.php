<?php

declare(strict_types=1);

namespace KraPHP\Contracts;

use KraPHP\Resources\ESlip;
use KraPHP\Resources\Etims;
use KraPHP\Resources\Excise;
use KraPHP\Resources\Pin;
use KraPHP\Resources\Returns;
use KraPHP\Resources\Tcc;
use KraPHP\Resources\Taxpayer;

/**
 * Interface for the KRA Client.
 *
 * This interface allows for mocking in unit tests.
 */
interface KraClientInterface
{
    /**
     * Get the PIN resource.
     */
    public function pin(): Pin;

    /**
     * Get the TCC resource.
     */
    public function tcc(): Tcc;

    /**
     * Get the Taxpayer resource.
     */
    public function taxpayer(): Taxpayer;

    /**
     * Get the Returns resource.
     */
    public function returns(): Returns;

    /**
     * Get the eTIMS resource.
     */
    public function etims(): Etims;

    /**
     * Get the e-Slip resource.
     */
    public function eslip(): ESlip;

    /**
     * Get the Excise resource.
     */
    public function excise(): Excise;

    /**
     * Clear all cached tokens.
     */
    public function clearTokens(): void;

    /**
     * Reset the circuit breaker.
     */
    public function resetCircuitBreaker(): void;
}
