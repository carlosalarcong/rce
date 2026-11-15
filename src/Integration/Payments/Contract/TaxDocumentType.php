<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Tipo de documento según el SII (código del tipo de DTE). */
enum TaxDocumentType: int
{
    case TaxableReceipt = 39;
    /** Las prestaciones de salud suelen ir exentas de IVA. */
    case ExemptReceipt = 41;
}
