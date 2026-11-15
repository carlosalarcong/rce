<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class PaymentStarted
{
    /**
     * @param string                $providerReference referencia del proveedor (Webpay: token)
     * @param array<string, string> $redirectFields    campos a enviar por POST al redirigir (Webpay: token_ws)
     */
    public function __construct(
        public string $providerReference,
        public PaymentNextStep $nextStep,
        public ?string $redirectUrl = null,
        public array $redirectFields = [],
    ) {
        if ('' === $providerReference) {
            throw new \InvalidArgumentException('Provider reference cannot be empty.');
        }

        if (PaymentNextStep::Redirect === $nextStep && (null === $redirectUrl || '' === $redirectUrl)) {
            throw new \InvalidArgumentException('A redirect step needs a redirect URL.');
        }
    }
}
