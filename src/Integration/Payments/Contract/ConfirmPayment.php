<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class ConfirmPayment
{
    /**
     * @param string                     $providerReference referencia del proveedor (Webpay: token; POS: orden de compra)
     * @param array<string, scalar|null> $terminalResponse  lo que respondió una máquina POS, reenviado por la pantalla de caja.
     *                                                      Vacío para proveedores que se consultan desde el servidor
     * @param Clp|null                   $expectedAmount    monto que se cobró; si viene, el adaptador lo compara con lo que dice el proveedor
     */
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $providerReference,
        public array $terminalResponse = [],
        public ?Clp $expectedAmount = null,
    ) {
        if ('' === $providerReference) {
            throw new \InvalidArgumentException('Provider reference cannot be empty.');
        }
    }
}
