<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/**
 * Encargo de diagnóstico: recorre toda la cadena (bandeja → aviso → /_tasks →
 * handler) sin efectos de negocio. Lo envía `bin/console rce:outbox:ping <tenant>`.
 */
final readonly class OutboxPing implements OutboxMessageInterface
{
    public function __construct(
        public string $tenant,
        public \DateTimeImmutable $sentAt,
    ) {
    }
}
