<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use App\Integration\Messaging\Application\OutboxRecord;
use App\Integration\Messaging\Application\OutboxStatus;
use App\Integration\Messaging\Application\OutboxStoreInterface;

/** Bandeja en memoria con la misma semántica que DbalOutboxStore (la real se prueba contra PostgreSQL). */
final class InMemoryOutboxStore implements OutboxStoreInterface
{
    /** @var array<string, array{record: OutboxRecord, error: ?string}> */
    public array $rows = [];

    /** @var array<string, list<string>> ids vencidos por tenant activo, para takeOverdue */
    public array $overdue = [];

    public function __construct(private readonly ?FakeActiveTenant $tenant = null)
    {
    }

    public function add(string $id, string $tenant, string $messageClass, string $body, array $headers, \DateTimeImmutable $now): void
    {
        $this->rows[$id] = ['record' => new OutboxRecord($id, $tenant, $messageClass, $body, $headers, OutboxStatus::Pending, 0, $now), 'error' => null];
    }

    public function claim(string $id, \DateTimeImmutable $now): ?OutboxRecord
    {
        $row = $this->rows[$id]['record'] ?? null;
        if (null === $row || OutboxStatus::Pending !== $row->status || $row->availableAt > $now) {
            return null;
        }

        return $this->put($id, OutboxStatus::Processing, $row->attempts + 1, $row->availableAt);
    }

    public function markDone(string $id, \DateTimeImmutable $now): void
    {
        $row = $this->rows[$id]['record'];
        $this->put($id, OutboxStatus::Done, $row->attempts, $row->availableAt);
    }

    public function markRetry(string $id, \DateTimeImmutable $retryAt, string $error): void
    {
        $this->put($id, OutboxStatus::Pending, $this->rows[$id]['record']->attempts, $retryAt, $error);
    }

    public function markFailed(string $id, \DateTimeImmutable $now, string $error): void
    {
        $row = $this->rows[$id]['record'];
        $this->put($id, OutboxStatus::Failed, $row->attempts, $row->availableAt, $error);
    }

    public function takeOverdue(\DateTimeImmutable $now, int $limit): array
    {
        return $this->overdue[(string) $this->tenant?->identifier()] ?? [];
    }

    public function status(string $id): OutboxStatus
    {
        return $this->rows[$id]['record']->status;
    }

    public function error(string $id): ?string
    {
        return $this->rows[$id]['error'];
    }

    private function put(string $id, OutboxStatus $status, int $attempts, \DateTimeImmutable $availableAt, ?string $error = null): OutboxRecord
    {
        $old = $this->rows[$id]['record'];
        $record = new OutboxRecord($id, $old->tenant, $old->messageClass, $old->body, $old->headers, $status, $attempts, $availableAt);
        $this->rows[$id] = ['record' => $record, 'error' => $error ?? $this->rows[$id]['error']];

        return $record;
    }
}
