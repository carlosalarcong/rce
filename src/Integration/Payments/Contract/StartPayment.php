<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class StartPayment
{
    /**
     * @param string      $buyOrder  orden de compra propia, única por cobro (Webpay acepta hasta 26 caracteres)
     * @param string|null $returnUrl a dónde vuelve el paciente tras pagar en la web del proveedor (null en POS)
     */
    public function __construct(
        public IdempotencyKey $idempotencyKey,
        public string $buyOrder,
        public Clp $amount,
        public ?string $returnUrl,
    ) {
        if (1 !== preg_match('/^[A-Za-z0-9_-]{1,26}$/', $buyOrder)) {
            throw new \InvalidArgumentException('Buy order must be 1 to 26 characters: letters, digits, "-" or "_".');
        }

        if (0 === $amount->value) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }
    }
}
