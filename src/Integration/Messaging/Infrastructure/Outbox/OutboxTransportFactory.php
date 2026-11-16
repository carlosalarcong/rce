<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Outbox;

use App\Integration\Messaging\Application\OutboxStoreInterface;
use App\Platform\Tenancy\Application\ActiveTenantInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/** @implements TransportFactoryInterface<OutboxTransport> */
#[AutoconfigureTag('messenger.transport_factory')]
final readonly class OutboxTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private OutboxStoreInterface $store,
        private ActiveTenantInterface $activeTenant,
        private PendingTaskNotifier $notifier,
        private ClockInterface $clock,
    ) {
    }

    /** @param array<mixed> $options */
    public function createTransport(#[\SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        return new OutboxTransport($this->store, $this->activeTenant, $this->notifier, $serializer, $this->clock);
    }

    /** @param array<mixed> $options */
    public function supports(#[\SensitiveParameter] string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'outbox://');
    }
}
