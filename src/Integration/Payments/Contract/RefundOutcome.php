<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class RefundOutcome
{
    /** @param array<string, scalar|null> $providerResponse */
    public function __construct(
        public bool $refunded,
        public string $providerReference,
        public Clp $amount,
        public array $providerResponse = [],
    ) {
    }
}
