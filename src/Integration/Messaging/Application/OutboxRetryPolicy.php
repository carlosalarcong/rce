<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

/**
 * Reintentos de un encargo que falló: al tiro, +1 min y +5 min (src/Integration/CLAUDE.md).
 * Son 4 intentos en total; después queda como fallido y genera alerta.
 */
final readonly class OutboxRetryPolicy
{
    /** Espera antes del reintento según cuántos intentos van, en segundos. */
    private const array DELAYS = [1 => 0, 2 => 60, 3 => 300];

    /** Cuándo reintentar tras el intento número $attempts, o null si ya no se reintenta. */
    public function nextAttemptAt(int $attempts, \DateTimeImmutable $now): ?\DateTimeImmutable
    {
        if ($attempts < 1) {
            throw new \InvalidArgumentException('Attempts start at 1.');
        }

        $delay = self::DELAYS[$attempts] ?? null;

        return null === $delay ? null : $now->modify(\sprintf('+%d seconds', $delay));
    }

    public function maxAttempts(): int
    {
        return \count(self::DELAYS) + 1;
    }
}
