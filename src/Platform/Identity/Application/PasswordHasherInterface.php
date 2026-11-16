<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

/** Cifra una contraseña (argon2id). La implementación usa la configuración de Symfony Security. */
interface PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string;
}
