<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class IssueVoucher
{
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public VoucherRequestData $data,
    ) {
    }
}
