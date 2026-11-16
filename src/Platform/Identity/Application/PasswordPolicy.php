<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

/**
 * Reglas de la contraseña. Largo antes que complejidad: 12 caracteres como
 * mínimo, sin exigir símbolos raros (que llevan a contraseñas anotadas en un papel).
 */
final readonly class PasswordPolicy
{
    public const int MIN_LENGTH = 12;
    public const int MAX_LENGTH = 4096;

    /** @throws WeakPasswordException */
    public function assertAcceptable(#[\SensitiveParameter] string $plainPassword, ?string $email = null): void
    {
        $length = mb_strlen($plainPassword);
        if ($length < self::MIN_LENGTH) {
            throw new WeakPasswordException(\sprintf('La contraseña debe tener al menos %d caracteres.', self::MIN_LENGTH));
        }
        if ($length > self::MAX_LENGTH) {
            throw new WeakPasswordException('La contraseña es demasiado larga.');
        }
        if ('' === trim($plainPassword)) {
            throw new WeakPasswordException('La contraseña no puede ser solo espacios.');
        }
        // Solo con 4 letras o más antes de la @: con "a@clinica.cl" casi toda contraseña contendría la "a".
        $local = null === $email ? '' : mb_strtolower((string) strstr($email, '@', true));
        if (mb_strlen($local) >= 4 && str_contains(mb_strtolower($plainPassword), $local)) {
            throw new WeakPasswordException('La contraseña no puede contener tu correo.');
        }
    }
}
