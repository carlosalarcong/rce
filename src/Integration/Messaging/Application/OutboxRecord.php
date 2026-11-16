<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/** Un encargo de la bandeja, tal como se guarda en la base del tenant. */
final readonly class OutboxRecord
{
    /**
     * @param array<string, string> $headers encabezados del sobre de Messenger
     */
    public function __construct(
        public string $id,
        public string $tenant,
        public string $messageClass,
        public string $body,
        public array $headers,
        public OutboxStatus $status,
        public int $attempts,
        public \DateTimeImmutable $availableAt,
    ) {
    }
}
