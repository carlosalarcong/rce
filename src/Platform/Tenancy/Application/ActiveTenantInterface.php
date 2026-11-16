<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Application;

/**
 * La clínica (tenant) a cuya base está conectado este proceso.
 *
 * Para código que corre fuera de una petición de usuario (tareas, workers,
 * comandos): ahí no hay sesión ni subdominio y la clínica llega explícita.
 */
interface ActiveTenantInterface
{
    /** Identificador del tenant activo (columna configurada en Hakam), o null si no hay ninguno. */
    public function identifier(): ?string;

    /** Conecta la base del tenant indicado. */
    public function switchTo(string $identifier): void;
}
