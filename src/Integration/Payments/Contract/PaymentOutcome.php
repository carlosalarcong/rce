<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class PaymentOutcome
{
    /**
     * @param array<string, scalar|null> $providerResponse respuesta del proveedor para auditoría, SIN datos de tarjeta
     */
    public function __construct(
        public PaymentResult $result,
        public string $providerReference,
        public Clp $amount,
        public ?string $authorizationCode = null,
        public ?\DateTimeImmutable $occurredAt = null,
        public array $providerResponse = [],
    ) {
        if (PaymentResult::Confirmed === $result && (null === $authorizationCode || '' === $authorizationCode)) {
            throw new \InvalidArgumentException('A confirmed payment needs an authorization code.');
        }
    }
}
