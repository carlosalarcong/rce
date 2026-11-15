<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class ConfirmPayment
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
