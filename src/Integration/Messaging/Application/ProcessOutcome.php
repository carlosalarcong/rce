<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

enum ProcessOutcome: string
{
    case Done = 'done';
    case RetryScheduled = 'retry_scheduled';
    case Failed = 'failed';
    /** Ya hecho, en manos de otro, aún no le toca o no existe: el aviso se descarta. */
    case NothingToDo = 'nothing_to_do';
}
