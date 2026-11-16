<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoucherSaleStarted
{
    /**
     * @param string                $providerReference con qué se consulta después (IMED: transacción y auditoría)
     * @param array<string, string> $redirectFields    campos a enviar por POST al abrir la ventana del proveedor
     * @param string|null           $rejectionReason   el proveedor no aceptó la venta (nextStep = None, sin referencia útil)
     */
    public function __construct(
        public string $providerReference,
        public PaymentNextStep $nextStep,
        public ?string $redirectUrl = null,
        public array $redirectFields = [],
        public ?string $rejectionReason = null,
    ) {
        if (null !== $rejectionReason) {
            if ('' === trim($rejectionReason) || PaymentNextStep::None !== $nextStep) {
                throw new \InvalidArgumentException('A rejected sale needs a reason and no next step.');
            }

            return;
        }
        if ('' === $providerReference) {
            throw new \InvalidArgumentException('Provider reference cannot be empty.');
        }
        if (PaymentNextStep::Redirect === $nextStep && (null === $redirectUrl || '' === $redirectUrl)) {
            throw new \InvalidArgumentException('A redirect step needs a redirect URL.');
        }
    }

    public static function rejected(string $reason): self
    {
        return new self('', PaymentNextStep::None, null, [], $reason);
    }

    public function isRejected(): bool
    {
        return null !== $this->rejectionReason;
    }
}
