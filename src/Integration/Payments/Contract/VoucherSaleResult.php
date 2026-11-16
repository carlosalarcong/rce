<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

enum VoucherSaleResult: string
{
    /** Emitida: trae uno o más bonos. */
    case Issued = 'issued';
    /** El paciente todavía no termina en la ventana del proveedor. */
    case Pending = 'pending';
    /** El proveedor no emitió (sin cobertura, error, anulada por el paciente). */
    case Rejected = 'rejected';
}
