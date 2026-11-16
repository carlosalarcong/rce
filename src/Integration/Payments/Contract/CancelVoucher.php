<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class CancelVoucher
{
    /**
     * @param string $reason      motivo, para auditoría (el proveedor puede no recibirlo)
     * @param string $insurerCode previsión con la que se emitió el bono
     * @param string $cashierRun  RUN normalizado de quien anula
     */
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $folio,
        public string $reason,
        public string $insurerCode,
        public string $cashierRun,
    ) {
        if ('' === trim($folio)) {
            throw new \InvalidArgumentException('Voucher folio cannot be empty.');
        }
        if ('' === trim($reason)) {
            throw new \InvalidArgumentException('Cancelling a voucher needs a reason.');
        }
        if ('' === trim($insurerCode)) {
            throw new \InvalidArgumentException('Insurer code cannot be empty.');
        }
        if (1 !== preg_match('/^\d{1,8}-[\dK]$/', $cashierRun)) {
            throw new \InvalidArgumentException('The cashier RUN must be normalized (digits, dash and check digit).');
        }
    }
}
