<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Llave única por operación. Un doble clic, un reintento o un webhook repetido
 * llegan con la misma llave y se detectan como "ya procesado".
 */
final readonly class IdempotencyKey
{
    private const string PATTERN = '/^[A-Za-z0-9_-]{8,64}$/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException('Idempotency key must be 8 to 64 characters: letters, digits, "-" or "_".');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
