<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Un bono emitido (un folio) con sus prestaciones. */
final readonly class IssuedVoucher
{
    /** @param list<IssuedVoucherLine> $lines */
    public function __construct(
        public string $folio,
        public Clp $total,
        public array $lines,
        public ?\DateTimeImmutable $issuedAt = null,
    ) {
        if ('' === trim($folio)) {
            throw new \InvalidArgumentException('Voucher folio cannot be empty.');
        }
    }

    public function covered(): Clp
    {
        return $this->sum(static fn (IssuedVoucherLine $l): int => $l->covered->value);
    }

    public function copay(): Clp
    {
        return $this->sum(static fn (IssuedVoucherLine $l): int => $l->copay->value);
    }

    public function supplementaryInsurance(): Clp
    {
        return $this->sum(static fn (IssuedVoucherLine $l): int => $l->supplementaryInsurance->value);
    }

    /** @param \Closure(IssuedVoucherLine): int $amount */
    private function sum(\Closure $amount): Clp
    {
        return new Clp(array_sum(array_map($amount, $this->lines)));
    }
}
