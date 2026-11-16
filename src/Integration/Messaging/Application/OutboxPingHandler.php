<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class OutboxPingHandler
{
    public function __construct(
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(OutboxPing $ping): void
    {
        $this->logger->info('Outbox ping processed.', [
            'tenant' => $ping->tenant,
            'latency_ms' => (int) round(((float) $this->clock->now()->format('U.u') - (float) $ping->sentAt->format('U.u')) * 1000),
        ]);
    }
}
