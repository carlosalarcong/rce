<?php

declare(strict_types=1);

namespace App\Integration\Messaging\UI;

use App\Integration\Messaging\Application\OutboxPing;
use App\Platform\Tenancy\Application\ActiveTenantInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

/** Diagnóstico de la cola en cualquier ambiente: deja un encargo "ping" en la bandeja de una clínica. */
#[AsCommand(name: 'rce:outbox:ping', description: 'Envía un encargo de diagnóstico por la cola de una clínica')]
final readonly class OutboxPingCommand
{
    public function __construct(
        private ActiveTenantInterface $activeTenant,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(OutputInterface $output, #[Argument(description: 'Identificador (subdominio) de la clínica')] string $tenant): int
    {
        $this->activeTenant->switchTo($tenant);
        $envelope = $this->bus->dispatch(new OutboxPing($tenant, $this->clock->now()));

        $id = $envelope->last(TransportMessageIdStamp::class)?->getId();
        $output->writeln(\sprintf('<info>Encargo %s guardado en la bandeja de "%s". El aviso sale al terminar el comando.</info>', \is_scalar($id) ? (string) $id : '?', $tenant));

        return Command::SUCCESS;
    }
}
