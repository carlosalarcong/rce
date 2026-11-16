<?php

declare(strict_types=1);

namespace App\Platform\Identity\Domain;

interface PasswordResetTokenRepositoryInterface
{
    public function byHash(string $tokenHash): ?PasswordResetToken;

    public function save(PasswordResetToken $token): void;

    /** Marca como usados todos los enlaces vigentes de la cuenta: solo el último sirve. */
    public function invalidateAllFor(string $accountId, \DateTimeImmutable $now): void;
}
