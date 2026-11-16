<?php

declare(strict_types=1);

namespace App\Platform\Identity\Domain;

/**
 * Enlace para elegir una contraseña nueva. Se guarda solo la huella (SHA-256)
 * del token: quien lea la base no puede usarlo. Sirve una sola vez y vence.
 *
 * No es `final`: Doctrine ORM 3 la necesita así (ADR 0002).
 */
class PasswordResetToken
{
    public const string LIFETIME = '30 minutes';

    private ?\DateTimeImmutable $usedAt = null;

    private function __construct(
        private string $id,
        private string $accountId,
        private string $tokenHash,
        private \DateTimeImmutable $expiresAt,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public static function issue(string $id, string $accountId, string $plainToken, \DateTimeImmutable $now): self
    {
        if (\strlen($plainToken) < 32) {
            throw new \InvalidArgumentException('Reset token is too short.');
        }
        $now = $now->setTimezone(new \DateTimeZone('UTC'));

        return new self($id, $accountId, self::hash($plainToken), $now->modify('+'.self::LIFETIME), $now);
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isUsable(\DateTimeImmutable $now): bool
    {
        return null === $this->usedAt && $now < $this->expiresAt;
    }

    public function markUsed(\DateTimeImmutable $now): void
    {
        if (null !== $this->usedAt) {
            throw new \LogicException('Reset token already used.');
        }
        $this->usedAt = $now->setTimezone(new \DateTimeZone('UTC'));
    }
}
