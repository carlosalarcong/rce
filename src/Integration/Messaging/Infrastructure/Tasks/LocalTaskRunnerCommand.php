<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Tasks;

use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/** Solo desarrollo: corre en el contenedor "tasks" de compose.yaml. En GCP no existe: lo hacen Cloud Tasks y Cloud Scheduler. */
#[AsCommand(name: 'rce:tasks:local-runner', description: 'Simulador local de Cloud Tasks y Cloud Scheduler para la cola de encargos')]
final readonly class LocalTaskRunnerCommand
{
    public function __construct(
        private LocalTaskRunner $runner,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(OutputInterface $output, #[Option(description: 'Una sola vuelta y termina')] bool $once = false): int
    {
        $output->writeln('<info>Simulador de Cloud Tasks encendido.</info>');

        do {
            $result = $this->runner->tick($this->clock->now());
            if ($result['delivered'] > 0 || $result['failed'] > 0) {
                $output->writeln(\sprintf('Entregados: %d, con error: %d', $result['delivered'], $result['failed']));
            }
            if (!$once) {
                usleep(500_000);
            }
        } while (!$once);

        return Command::SUCCESS;
    }
}
