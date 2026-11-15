<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class TaxDocumentIssued
{
    /** @param array<string, scalar|null> $providerResponse */
    public function __construct(
        public TaxDocumentType $type,
        public int $folio,
        public Clp $total,
        public ?string $pdfUrl = null,
        public array $providerResponse = [],
    ) {
        if ($folio < 1) {
            throw new \InvalidArgumentException('Tax document folio must be positive.');
        }
    }
}
