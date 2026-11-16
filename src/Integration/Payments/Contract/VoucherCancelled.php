<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

final readonly class VoucherCancelled
{
    /**
     * @param bool                       $cancelled        false: el proveedor NO anuló el bono, sigue vigente
     * @param string|null                $reason           motivo del proveedor cuando no anuló
     * @param array<string, scalar|null> $providerResponse
     */
    public function __construct(
        public bool $cancelled,
        public string $folio,
        public ?\DateTimeImmutable $cancelledAt = null,
        public ?string $reason = null,
        public array $providerResponse = [],
    ) {
        if (!$cancelled && (null === $reason || '' === trim($reason))) {
            throw new \InvalidArgumentException('A cancellation that failed needs a reason.');
        }
    }
}
