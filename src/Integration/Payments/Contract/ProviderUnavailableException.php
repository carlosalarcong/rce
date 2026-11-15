<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * El proveedor no respondió (timeout, caída, error 5xx). Es reintentable y
 * cuenta como falla para el circuit breaker. Un rechazo de negocio NO es esto:
 * es un resultado con estado REJECTED.
 */
final class ProviderUnavailableException extends \RuntimeException
{
    public static function because(string $provider, string $reason, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Provider "%s" unavailable: %s', $provider, $reason), 0, $previous);
    }
}
