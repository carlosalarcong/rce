<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoucherCancelled
{
    /** @param array<string, scalar|null> $providerResponse */
    public function __construct(
        public string $folio,
        public \DateTimeImmutable $cancelledAt,
        public array $providerResponse = [],
    ) {
    }
}
