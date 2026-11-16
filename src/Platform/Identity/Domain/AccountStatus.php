<?php

declare(strict_types=1);

namespace App\Platform\Identity\Domain;

enum AccountStatus: string
{
    case Active = 'active';
    /** No puede entrar aunque su contraseña sea correcta. Sus registros se conservan. */
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Suspended => 'Suspendida',
        };
    }
}
