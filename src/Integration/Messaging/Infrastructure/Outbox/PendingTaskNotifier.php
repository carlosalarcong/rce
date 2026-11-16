<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Outbox;

use App\Integration\Messaging\Application\TaskQueueInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Avisa a la cola (Cloud Tasks o simulador) los encargos guardados durante la
 * petición o el comando, cuando ya terminó: así la transacción ya se confirmó.
 * Si se deshizo, el aviso llega y no encuentra nada (NothingToDo). Si un aviso
 * falla, la barrida lo reenvía.
 */
final class PendingTaskNotifier implements ResetInterface
{
    /** @var array<string, array{string, string}> */
    private array $pending = [];

    public function __construct(
        private readonly TaskQueueInterface $tasks,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function add(string $tenant, string $messageId): void
    {
        $this->pending[$tenant.'|'.$messageId] = [$tenant, $messageId];
    }

    #[AsEventListener(event: KernelEvents::TERMINATE)]
    #[AsEventListener(event: ConsoleEvents::TERMINATE)]
    public function flush(): void
    {
        $pending = $this->pending;
        $this->pending = [];

        foreach ($pending as [$tenant, $messageId]) {
            try {
                $this->tasks->schedule($tenant, $messageId, $this->clock->now());
            } catch (\Throwable $error) {
                $this->logger->warning('Outbox notification failed; the sweep will retry it.', [
                    'tenant' => $tenant,
                    'message_id' => $messageId,
                    'error_class' => $error::class,
                ]);
            }
        }
    }

    public function reset(): void
    {
        $this->pending = [];
    }
}
