<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Monto en pesos chilenos, entero y sin decimales. Nunca float. */
final readonly class Clp
{
    public function __construct(public int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(\sprintf('Amount cannot be negative, got %d.', $value));
        }
    }

    public static function positive(int $value): self
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(\sprintf('Amount must be greater than zero, got %d.', $value));
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
