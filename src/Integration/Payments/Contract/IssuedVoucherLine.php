<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Una prestación bonificada, tal como la devolvió el proveedor. */
final readonly class IssuedVoucherLine
{
    /**
     * @param Clp         $amount                 valor de la prestación
     * @param Clp         $covered                lo que paga la previsión
     * @param Clp         $copay                  lo que paga el paciente
     * @param Clp         $supplementaryInsurance lo que cubre un seguro complementario (sale del copago)
     * @param string|null $gesPathologyCode       código de problema de salud GES, si es GES
     */
    public function __construct(
        public string $benefitCode,
        public string $itemCode,
        public int $quantity,
        public Clp $amount,
        public Clp $covered,
        public Clp $copay,
        public Clp $supplementaryInsurance,
        public bool $ges = false,
        public ?string $gesPathologyCode = null,
    ) {
    }
}
