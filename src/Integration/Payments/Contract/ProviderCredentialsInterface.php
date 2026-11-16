<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Entrega las credenciales de un proveedor para el tenant actual. */
interface ProviderCredentialsInterface
{
    /** @throws ProviderNotConfiguredException si el tenant no lo tiene configurado */
    public function for(string $provider): ProviderCredentials;
}
