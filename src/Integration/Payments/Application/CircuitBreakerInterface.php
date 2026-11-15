<?php

declare(strict_types=1);

namespace App\Integration\Payments\Application;

/**
 * Circuit breaker por proveedor: tras fallas repetidas se deja de llamarlo un
 * tiempo y los procesos quedan en cola. Lo usan los workers, alrededor de cada
 * llamada al proveedor.
 */
interface CircuitBreakerInterface
{
    public function isAvailable(string $provider): bool;

    public function recordSuccess(string $provider): void;

    /** Solo fallas técnicas (ProviderUnavailableException), no rechazos de negocio. */
    public function recordFailure(string $provider): void;
}
