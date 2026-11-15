<?php

declare(strict_types=1);

namespace App\Integration\Payments\Infrastructure\Configuration;

use App\Integration\Payments\Application\ActiveProvidersInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Mientras no exista la configuración de proveedores por tenant, ninguno está
 * activo: un proveedor no activo no se puede usar aunque su adaptador exista.
 * Se reemplaza por la lectura desde la base del tenant (docs/adr/0004).
 */
#[AsAlias(ActiveProvidersInterface::class)]
final readonly class NoActiveProviders implements ActiveProvidersInterface
{
    public function isActive(string $provider): bool
    {
        return false;
    }
}
