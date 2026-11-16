<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/**
 * Marca un mensaje de Messenger que va a la bandeja de encargos (outbox) del
 * tenant: se guarda en la misma transacción que el cambio que lo origina y lo
 * procesa una tarea (Cloud Tasks en GCP; el simulador en local), con reintentos.
 *
 * El mensaje viaja serializado en la base del tenant: solo ids y datos
 * necesarios, nunca datos clínicos que no hagan falta.
 */
interface OutboxMessageInterface
{
}
