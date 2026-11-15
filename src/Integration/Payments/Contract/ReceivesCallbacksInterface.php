<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Para adaptadores que reciben webhooks o retornos. Validar firma u origen NO
 * basta para aceptar un estado: después se consulta al proveedor (confirm()).
 */
interface ReceivesCallbacksInterface extends ProviderInterface
{
    /** Firma u origen válidos. Si es false, el callback se descarta y se audita. */
    public function verifyCallback(ProviderCallback $callback): bool;

    /** La referencia del proveedor (token, id de transacción, folio) que trae el callback. */
    public function referenceFrom(ProviderCallback $callback): string;
}
