<?php

declare(strict_types=1);

namespace App\Integration\Payments\Infrastructure\Configuration;

use App\Integration\Payments\Contract\ProviderCredentials;
use App\Integration\Payments\Contract\ProviderCredentialsInterface;
use App\Integration\Payments\Contract\ProviderNotConfiguredException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Producción, mientras no exista la configuración de proveedores por tenant
 * (tabla cifrada en la base del tenant, docs/adr/0004): ningún proveedor tiene
 * credenciales. En dev y test se reemplaza por SandboxProviderCredentials.
 */
#[AsAlias(ProviderCredentialsInterface::class)]
final readonly class NoProviderCredentials implements ProviderCredentialsInterface
{
    public function for(string $provider): ProviderCredentials
    {
        throw ProviderNotConfiguredException::forProvider($provider);
    }
}
