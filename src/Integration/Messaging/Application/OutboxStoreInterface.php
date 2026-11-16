<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/** La bandeja de encargos en la base del tenant activo. */
interface OutboxStoreInterface
{
    /**
     * Guarda un encargo nuevo, en la transacción abierta si la hay (outbox).
     *
     * @param array<string, string> $headers
     */
    public function add(string $id, string $tenant, string $messageClass, string $body, array $headers, \DateTimeImmutable $now): void;

    /**
     * Toma el encargo para procesarlo, de forma atómica: solo si está pendiente y
     * ya le toca. Suma un intento. Null si no existe, ya lo tomó otro, ya está
     * hecho o aún no le toca: así un aviso repetido no lo procesa dos veces.
     */
    public function claim(string $id, \DateTimeImmutable $now): ?OutboxRecord;

    public function markDone(string $id, \DateTimeImmutable $now): void;

    /** Vuelve a pendiente para reintentar desde $retryAt. $error: solo la clase del error, nunca su mensaje. */
    public function markRetry(string $id, \DateTimeImmutable $retryAt, string $error): void;

    public function markFailed(string $id, \DateTimeImmutable $now, string $error): void;

    /**
     * Encargos pendientes que ya debieron avisarse y no se procesaron (aviso
     * perdido), o tomados hace mucho por un proceso que murió (los devuelve a
     * pendiente). Los marca como avisados en $now.
     *
     * @return list<string> ids
     */
    public function takeOverdue(\DateTimeImmutable $now, int $limit): array;
}
