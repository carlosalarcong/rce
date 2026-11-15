<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Una prestación del bono: código de prestación (arancel Fonasa/Isapre), cantidad y valor. */
final readonly class VoucherLine
{
    public function __construct(
        public string $benefitCode,
        public int $quantity,
        public Clp $amount,
    ) {
        if ('' === trim($benefitCode)) {
            throw new \InvalidArgumentException('Benefit code cannot be empty.');
        }

        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }
    }
}
