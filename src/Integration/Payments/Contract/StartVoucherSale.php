<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Datos para iniciar la venta de bonos.
 *
 * Los RUN llegan normalizados (sin puntos, con guion). Cuando exista el value
 * object Run de Platform se cambia el tipo (docs/adr/0004). Los códigos
 * (previsión, especialidad, prestaciones) ya vienen traducidos al proveedor.
 */
final readonly class StartVoucherSale
{
    private const string RUN_PATTERN = '/^\d{1,8}-[\dK]$/';

    /**
     * @param string            $insurerCode     código de la previsión en el proveedor (IMED: CodFinanciador)
     * @param string|null       $professionalRun profesional tratante; null si no aplica
     * @param string|null       $requesterRun    quien solicita o deriva; null si no aplica
     * @param string|null       $requesterName   nombre de quien solicita, si no tiene RUN en el sistema
     * @param list<VoucherLine> $lines
     * @param string            $returnUrl       a dónde vuelve el navegador desde la ventana del proveedor
     */
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $insurerCode,
        public string $patientRun,
        public string $cashierRun,
        public array $lines,
        public string $returnUrl,
        public ?string $professionalRun = null,
        public string $specialtyCode = '0',
        public ?string $requesterRun = null,
        public ?string $requesterName = null,
        public bool $emergency = false,
    ) {
        if ([] === $lines) {
            throw new \InvalidArgumentException('A voucher sale needs at least one line.');
        }
        if ('' === trim($insurerCode)) {
            throw new \InvalidArgumentException('Insurer code cannot be empty.');
        }
        if ('' === trim($returnUrl)) {
            throw new \InvalidArgumentException('Return URL cannot be empty.');
        }
        foreach (['patient' => $patientRun, 'cashier' => $cashierRun, 'professional' => $professionalRun, 'requester' => $requesterRun] as $who => $run) {
            if (null !== $run && 1 !== preg_match(self::RUN_PATTERN, $run)) {
                throw new \InvalidArgumentException(\sprintf('The %s RUN must be normalized (digits, dash and check digit).', $who));
            }
        }
    }

    public function total(): Clp
    {
        return new Clp(array_sum(array_map(static fn (VoucherLine $l): int => $l->total()->value, $this->lines)));
    }
}
