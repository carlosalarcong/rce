<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Una prestación a bonificar, con el código que entiende el proveedor (ya
 * traducido desde el arancel del centro por quien llama).
 */
final readonly class VoucherLine
{
    /**
     * @param string $itemCode tipo de ítem del proveedor (IMED: "0" prestación; "8" pabellón, "1" cirujano, "2" anestesista)
     */
    public function __construct(
        public string $benefitCode,
        public int $quantity,
        public Clp $amount,
        public string $itemCode = '0',
    ) {
        if ('' === trim($benefitCode)) {
            throw new \InvalidArgumentException('Benefit code cannot be empty.');
        }
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }
        if (1 !== preg_match('/^\d{1,3}$/', $itemCode)) {
            throw new \InvalidArgumentException('Item code must be 1 to 3 digits.');
        }
    }

    public function total(): Clp
    {
        return new Clp($this->amount->value * $this->quantity);
    }
}
