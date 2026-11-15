<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class TaxDocumentLine
{
    public function __construct(
        public string $description,
        public int $quantity,
        public Clp $unitPrice,
    ) {
        if ('' === trim($description)) {
            throw new \InvalidArgumentException('Line description cannot be empty.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }
    }

    public function total(): Clp
    {
        return new Clp($this->unitPrice->value * $this->quantity);
    }
}
