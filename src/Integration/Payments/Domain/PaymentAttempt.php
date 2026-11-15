<?php

declare(strict_types=1);

namespace App\Integration\Payments\Domain;

/**
 * Un intento de cobro con un proveedor. Se crea en "started" y solo avanza por
 * la máquina de estados "payment_attempt" (nunca retrocede ni salta estados).
 *
 * Todavía sin mapeo ni tabla: se agregan cuando se implemente el primer cobro.
 * No es final: Doctrine ORM 3 lo necesita (docs/adr/0002).
 */
class PaymentAttempt
{
    /** Lo escribe y lee el marking store de Symfony Workflow (getStatus/setStatus). */
    private string $status;

    public function __construct(
        private readonly string $id,
        private readonly string $provider,
        private readonly string $idempotencyKey,
        private readonly int $amount,
    ) {
        if ('' === $id || '' === $provider || '' === $idempotencyKey) {
            throw new \InvalidArgumentException('Payment attempt needs an id, a provider and an idempotency key.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $this->status = PaymentStatus::Started->value;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    /** En pesos, entero. */
    public function amount(): int
    {
        return $this->amount;
    }

    public function status(): PaymentStatus
    {
        return PaymentStatus::from($this->status);
    }

    /** @internal solo para el marking store de Symfony Workflow */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @internal solo para el marking store de Symfony Workflow, que valida la transición antes de llamarlo
     *
     * @param array<string, mixed> $context
     */
    public function setStatus(string $status, array $context = []): void
    {
        $this->status = PaymentStatus::from($status)->value;
    }
}
