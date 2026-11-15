<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoucherValidation
{
    public function __construct(
        public bool $eligible,
        public Clp $coveredAmount,
        public Clp $copayAmount,
        public ?string $reason = null,
    ) {
        if (!$eligible && (null === $reason || '' === $reason)) {
            throw new \InvalidArgumentException('A non eligible validation needs a reason.');
        }
    }
}
