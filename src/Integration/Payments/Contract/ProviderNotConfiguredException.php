<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * El tenant no tiene credenciales (o le faltan) para este proveedor. NO es
 * reintentable ni cuenta para el circuit breaker: hay que configurarlo.
 */
final class ProviderNotConfiguredException extends \RuntimeException
{
    public static function forProvider(string $provider): self
    {
        return new self(\sprintf('Provider "%s" is not configured for this tenant.', $provider));
    }
}
