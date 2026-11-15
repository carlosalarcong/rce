<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoucherIssued
{
    /** @param array<string, scalar|null> $providerResponse */
    public function __construct(
        public string $folio,
        public Clp $coveredAmount,
        public Clp $copayAmount,
        public \DateTimeImmutable $issuedAt,
        public array $providerResponse = [],
    ) {
        if ('' === trim($folio)) {
            throw new \InvalidArgumentException('Voucher folio cannot be empty.');
        }
    }
}
