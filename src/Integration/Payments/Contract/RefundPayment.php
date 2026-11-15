<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class RefundPayment
{
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $providerReference,
        public Clp $amount,
    ) {
        if ('' === $providerReference) {
            throw new \InvalidArgumentException('Provider reference cannot be empty.');
        }

        if (0 === $amount->value) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }
    }
}
