<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class ConfirmVoucherSale
{
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $providerReference,
    ) {
        if ('' === $providerReference) {
            throw new \InvalidArgumentException('Provider reference cannot be empty.');
        }
    }
}
