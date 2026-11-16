<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoucherSaleOutcome
{
    /**
     * @param list<IssuedVoucher>        $vouchers         bonos emitidos (solo si Issued)
     * @param Clp                        $surplus          excedente que paga el paciente aparte (Isapre)
     * @param array<string, scalar|null> $providerResponse respuesta del proveedor para auditoría, SIN datos del paciente
     */
    public function __construct(
        public VoucherSaleResult $result,
        public string $providerReference,
        public array $vouchers = [],
        public Clp $surplus = new Clp(0),
        public ?string $reason = null,
        public array $providerResponse = [],
    ) {
        if (VoucherSaleResult::Issued === $result && [] === $vouchers) {
            throw new \InvalidArgumentException('An issued sale needs at least one voucher.');
        }
        if (VoucherSaleResult::Issued !== $result && [] !== $vouchers) {
            throw new \InvalidArgumentException('Only an issued sale has vouchers.');
        }
    }

    /** @return list<string> */
    public function folios(): array
    {
        return array_map(static fn (IssuedVoucher $v): string => $v->folio, $this->vouchers);
    }
}
