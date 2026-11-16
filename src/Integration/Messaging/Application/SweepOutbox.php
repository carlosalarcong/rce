<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

use App\Platform\Tenancy\Application\ActiveTenantInterface;
use App\Platform\Tenancy\Application\TenantDirectoryInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Red de seguridad, cada minuto (Cloud Scheduler en GCP; el simulador en
 * local): recorre todas las clínicas y vuelve a avisar los encargos cuyo aviso
 * se perdió o cuyo proceso murió. Un aviso de más no hace daño: claim() es atómico.
 */
final readonly class SweepOutbox
{
    private const int LIMIT_PER_TENANT = 200;

    public function __construct(
        private TenantDirectoryInterface $tenants,
        private ActiveTenantInterface $activeTenant,
        private OutboxStoreInterface $store,
        private TaskQueueInterface $tasks,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    /** @return int cuántos avisos se volvieron a enviar */
    public function __invoke(): int
    {
        $rescheduled = 0;

        foreach ($this->tenants->identifiers() as $tenant) {
            try {
                $this->activeTenant->switchTo($tenant);
                $now = $this->clock->now();
                foreach ($this->store->takeOverdue($now, self::LIMIT_PER_TENANT) as $messageId) {
                    $this->tasks->schedule($tenant, $messageId, $now);
                    ++$rescheduled;
                }
            } catch (\Throwable $error) {
                // Una clínica con problemas no detiene la barrida de las demás.
                $this->logger->error('Outbox sweep failed for a tenant.', ['tenant' => $tenant, 'error_class' => $error::class]);
            }
        }

        return $rescheduled;
    }
}
