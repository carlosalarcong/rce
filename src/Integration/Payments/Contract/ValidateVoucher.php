<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class ValidateVoucher
{
    /** @param string|null $folio solo para bonos ya emitidos fuera del sistema (folio manual) */
    public function __construct(
        public VoucherRequestData $data,
        public ?string $folio = null,
    ) {
    }
}
