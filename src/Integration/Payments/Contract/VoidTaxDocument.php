<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoidTaxDocument
{
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public TaxDocumentType $type,
        public int $folio,
        public string $reason,
    ) {
        if ($folio < 1) {
            throw new \InvalidArgumentException('Tax document folio must be positive.');
        }

        if ('' === trim($reason)) {
            throw new \InvalidArgumentException('Voiding a tax document needs a reason.');
        }
    }
}
