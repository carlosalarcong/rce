<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

/** Enlace inexistente, vencido o ya usado: a la persona se le dice lo mismo en los tres casos. */
final class InvalidResetTokenException extends \DomainException
{
}
