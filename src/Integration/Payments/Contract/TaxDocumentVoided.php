<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class TaxDocumentVoided
{
    /** @param array<string, scalar|null> $providerResponse */
    public function __construct(
        public int $folio,
        public ?int $creditNoteFolio = null,
        public array $providerResponse = [],
    ) {
    }
}
