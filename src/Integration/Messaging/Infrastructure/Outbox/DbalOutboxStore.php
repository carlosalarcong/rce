<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Outbox;

use App\Integration\Messaging\Application\OutboxRecord;
use App\Integration\Messaging\Application\OutboxStatus;
use App\Integration\Messaging\Application\OutboxStoreInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Bandeja de encargos (tabla outbox_message) en la base del tenant activo.
 *
 * Usa la conexión "tenant", la misma del TenantEntityManager: un encargo
 * guardado dentro de una transacción de negocio se confirma o se deshace con ella.
 */
#[AsAlias(OutboxStoreInterface::class)]
final readonly class DbalOutboxStore implements OutboxStoreInterface
{
    /** Un encargo tomado hace más que esto se considera de un proceso muerto. */
    private const string STALE_LOCK = '10 minutes';

    /** Un aviso que no llegó en este tiempo se vuelve a enviar. */
    private const string NOTIFY_GRACE = '2 minutes';

    private const string TABLE = 'outbox_message';

    public function __construct(
        #[Autowire(service: 'doctrine.dbal.tenant_connection')]
        private Connection $connection,
    ) {
    }

    public function add(string $id, string $tenant, string $messageClass, string $body, array $headers, \DateTimeImmutable $now): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $id,
            'tenant' => $tenant,
            'message_class' => $messageClass,
            'body' => $body,
            'headers' => json_encode($headers, \JSON_THROW_ON_ERROR),
            'status' => OutboxStatus::Pending->value,
            'attempts' => 0,
            'available_at' => $this->ts($now),
            // El aviso sale apenas termina la petición (PendingTaskNotifier).
            'notified_at' => $this->ts($now),
            'created_at' => $this->ts($now),
            'updated_at' => $this->ts($now),
        ]);
    }

    public function claim(string $id, \DateTimeImmutable $now): ?OutboxRecord
    {
        $row = $this->connection->fetchAssociative(
            'UPDATE outbox_message
                SET status = :processing, attempts = attempts + 1, locked_at = :now, updated_at = :now
              WHERE id = :id AND status = :pending AND available_at <= :now
          RETURNING id, tenant, message_class, body, headers, status, attempts, available_at',
            ['id' => $id, 'now' => $this->ts($now), 'pending' => OutboxStatus::Pending->value, 'processing' => OutboxStatus::Processing->value],
        );

        return false === $row ? null : $this->record($row);
    }

    public function markDone(string $id, \DateTimeImmutable $now): void
    {
        $this->connection->update(self::TABLE, [
            'status' => OutboxStatus::Done->value,
            'locked_at' => null,
            'last_error' => null,
            'updated_at' => $this->ts($now),
        ], ['id' => $id]);
    }

    public function markRetry(string $id, \DateTimeImmutable $retryAt, string $error): void
    {
        $this->connection->update(self::TABLE, [
            'status' => OutboxStatus::Pending->value,
            'available_at' => $this->ts($retryAt),
            // El aviso del reintento está programado para $retryAt.
            'notified_at' => $this->ts($retryAt),
            'locked_at' => null,
            'last_error' => mb_substr($error, 0, 255),
            'updated_at' => $this->ts($retryAt),
        ], ['id' => $id]);
    }

    public function markFailed(string $id, \DateTimeImmutable $now, string $error): void
    {
        $this->connection->update(self::TABLE, [
            'status' => OutboxStatus::Failed->value,
            'locked_at' => null,
            'last_error' => mb_substr($error, 0, 255),
            'updated_at' => $this->ts($now),
        ], ['id' => $id]);
    }

    public function takeOverdue(\DateTimeImmutable $now, int $limit): array
    {
        $this->connection->executeStatement(
            'UPDATE outbox_message
                SET status = :pending, locked_at = NULL, updated_at = :now
              WHERE status = :processing AND locked_at < :stale',
            [
                'pending' => OutboxStatus::Pending->value,
                'processing' => OutboxStatus::Processing->value,
                'now' => $this->ts($now),
                'stale' => $this->ts($now->modify('-'.self::STALE_LOCK)),
            ],
        );

        /** @var list<string> $ids */
        $ids = $this->connection->fetchFirstColumn(
            'UPDATE outbox_message SET notified_at = :now
              WHERE id IN (
                    SELECT id FROM outbox_message
                     WHERE status = :pending AND available_at <= :now
                       AND (notified_at IS NULL OR notified_at < :grace)
                  ORDER BY available_at
                     LIMIT :limit
                FOR UPDATE SKIP LOCKED)
          RETURNING id',
            [
                'now' => $this->ts($now),
                'pending' => OutboxStatus::Pending->value,
                'grace' => $this->ts($now->modify('-'.self::NOTIFY_GRACE)),
                'limit' => $limit,
            ],
            ['limit' => \Doctrine\DBAL\ParameterType::INTEGER],
        );

        return $ids;
    }

    /** @param array<string, mixed> $row */
    private function record(array $row): OutboxRecord
    {
        $decoded = json_decode($this->str($row['headers'] ?? '{}'), true, 512, \JSON_THROW_ON_ERROR);
        $headers = [];
        foreach (\is_array($decoded) ? $decoded : [] as $name => $value) {
            if (\is_string($value)) {
                $headers[(string) $name] = $value;
            }
        }

        return new OutboxRecord(
            $this->str($row['id'] ?? null),
            $this->str($row['tenant'] ?? null),
            $this->str($row['message_class'] ?? null),
            $this->str($row['body'] ?? null),
            $headers,
            OutboxStatus::from($this->str($row['status'] ?? null)),
            (int) $this->str($row['attempts'] ?? null),
            new \DateTimeImmutable($this->str($row['available_at'] ?? null)),
        );
    }

    private function str(mixed $value): string
    {
        if (!\is_scalar($value)) {
            throw new \UnexpectedValueException('Unexpected outbox column value.');
        }

        return (string) $value;
    }

    private function ts(\DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
    }
}
