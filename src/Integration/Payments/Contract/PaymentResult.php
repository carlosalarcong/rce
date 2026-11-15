<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

enum PaymentResult: string
{
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    /** El proveedor aún no tiene respuesta final (ej.: el paciente no termina en el POS). */
    case Pending = 'pending';
}
