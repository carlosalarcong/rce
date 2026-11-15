<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class CancelVoucher
{
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $folio,
        public string $reason,
    ) {
        if ('' === trim($folio)) {
            throw new \InvalidArgumentException('Voucher folio cannot be empty.');
        }

        if ('' === trim($reason)) {
            throw new \InvalidArgumentException('Cancelling a voucher needs a reason.');
        }
    }
}
