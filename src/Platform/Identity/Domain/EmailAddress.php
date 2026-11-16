<?php

declare(strict_types=1);

namespace App\Platform\Identity\Domain;

/**
 * Correo normalizado (sin espacios y en minúsculas). Sirve para entrar y para
 * escribirle a la persona, nunca para identificarla: eso es el UUID de la cuenta.
 */
final readonly class EmailAddress
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));
        if ('' === $normalized || mb_strlen($normalized) > 180 || false === filter_var($normalized, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
