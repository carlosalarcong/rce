<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class IssueTaxDocument
{
    /**
     * @param list<TaxDocumentLine> $lines
     * @param string|null           $receiverRut RUT del receptor normalizado; null = consumidor final
     */
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public TaxDocumentType $type,
        public array $lines,
        public \DateTimeImmutable $issuedOn,
        public ?string $receiverRut = null,
    ) {
        if ([] === $lines) {
            throw new \InvalidArgumentException('A tax document needs at least one line.');
        }
    }

    public function total(): Clp
    {
        return new Clp(array_sum(array_map(static fn (TaxDocumentLine $l): int => $l->total()->value, $this->lines)));
    }
}
