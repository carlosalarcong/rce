<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Outbox;

use App\Integration\Messaging\Application\OutboxStoreInterface;
use App\Platform\Tenancy\Application\ActiveTenantInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Transporte "outbox://": guarda el mensaje en la bandeja del tenant activo.
 *
 * Solo envía. No se consume con messenger:consume: los encargos los procesa
 * ProcessOutboxMessage cuando la cola llama a /_tasks.
 */
final readonly class OutboxTransport implements TransportInterface
{
    public function __construct(
        private OutboxStoreInterface $store,
        private ActiveTenantInterface $activeTenant,
        private PendingTaskNotifier $notifier,
        private SerializerInterface $serializer,
        private ClockInterface $clock,
    ) {
    }

    public function send(Envelope $envelope): Envelope
    {
        $tenant = $this->activeTenant->identifier()
            ?? throw new \LogicException('The outbox needs an active tenant: dispatch outbox messages inside a tenant context.');

        $encoded = $this->serializer->encode($envelope);
        $id = self::uuid();

        $this->store->add($id, $tenant, $envelope->getMessage()::class, $encoded['body'], $encoded['headers'] ?? [], $this->clock->now());
        $this->notifier->add($tenant, $id);

        return $envelope->with(new TransportMessageIdStamp($id));
    }

    public function get(): iterable
    {
        return [];
    }

    public function ack(Envelope $envelope): void
    {
    }

    public function reject(Envelope $envelope): void
    {
    }

    /** UUID v4. */
    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
