<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Lo común a validar y emitir un bono.
 *
 * Los RUN llegan normalizados (sin puntos, con guion). Cuando exista el value
 * object Run de Platform se cambia el tipo (docs/adr/0004).
 */
final readonly class VoucherRequestData
{
    /** @param list<VoucherLine> $lines */
    public function __construct(
        public string $patientRun,
        public string $insurerCode,
        public string $professionalRun,
        public array $lines,
    ) {
        if ([] === $lines) {
            throw new \InvalidArgumentException('A voucher needs at least one line.');
        }

        foreach (['patientRun' => $patientRun, 'insurerCode' => $insurerCode, 'professionalRun' => $professionalRun] as $field => $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException(\sprintf('%s cannot be empty.', $field));
            }
        }
    }

    public function total(): Clp
    {
        return new Clp(array_sum(array_map(static fn (VoucherLine $l): int => $l->amount->value * $l->quantity, $this->lines)));
    }
}
