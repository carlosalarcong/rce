<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/**
 * El "timbre": pide que, a partir de $at, se llame al RCE para procesar un
 * encargo de la bandeja. GCP: Cloud Tasks. Local: el simulador (contenedor tasks).
 *
 * Que un aviso se pierda no pierde el encargo: la barrida (SweepOutbox) vuelve a avisar.
 */
interface TaskQueueInterface
{
    public function schedule(string $tenant, string $messageId, \DateTimeImmutable $at): void;
}
