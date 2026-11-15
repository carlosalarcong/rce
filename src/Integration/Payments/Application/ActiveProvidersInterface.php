<?php

declare(strict_types=1);

namespace App\Integration\Payments\Application;

/** Qué proveedores tiene activos el tenant actual (configuración en la base del tenant). */
interface ActiveProvidersInterface
{
    public function isActive(string $provider): bool;
}
