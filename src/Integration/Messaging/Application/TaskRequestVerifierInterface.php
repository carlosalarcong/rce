<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/**
 * Verifica que una llamada a /_tasks venga de la cola y no de cualquiera.
 * GCP: token OIDC firmado por Google para la cuenta de servicio de Cloud Tasks.
 * Local: token compartido con el simulador.
 */
interface TaskRequestVerifierInterface
{
    /** @param string|null $authorization valor del encabezado Authorization */
    public function isTrusted(?string $authorization): bool;
}
