<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Qué pasa después de iniciar un cobro. */
enum PaymentNextStep: string
{
    /** Llevar al paciente a la página del proveedor (Webpay). */
    case Redirect = 'redirect';
    /** Esperar a que el paciente pague en la máquina (POS de Transbank). */
    case WaitForTerminal = 'wait_for_terminal';
    /** Nada: el cobro queda listo para confirmar (POS manual). */
    case None = 'none';
}
