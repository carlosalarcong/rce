<?php

declare(strict_types=1);

namespace App\Platform\Identity\Domain;

/**
 * La cuenta de una persona en el RCE. Vive en la base Main: es de la persona,
 * no de una clínica (a qué clínicas entra lo dice su membresía, paso 2 de la
 * ADR 0005). Se identifica por su UUID; el correo solo sirve para entrar.
 *
 * No es `final`: Doctrine ORM 3 la necesita así (ADR 0002). El mapeo es XML en
 * Infrastructure.
 */
class UserAccount
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private string $email;
    private AccountStatus $status;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $lastLoginAt = null;

    private function __construct(
        private string $id,
        EmailAddress $email,
        private string $displayName,
        private string $passwordHash,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->email = $email->value;
        $this->status = AccountStatus::Active;
        $this->updatedAt = $createdAt;
    }

    public static function create(string $id, EmailAddress $email, string $displayName, string $passwordHash, \DateTimeImmutable $now): self
    {
        if (1 !== preg_match(self::UUID_PATTERN, $id)) {
            throw new \InvalidArgumentException('Account id must be a lowercase UUID.');
        }
        $displayName = trim($displayName);
        if ('' === $displayName || mb_strlen($displayName) > 120) {
            throw new \InvalidArgumentException('Display name must have 1 to 120 characters.');
        }
        if ('' === $passwordHash) {
            throw new \InvalidArgumentException('Password hash cannot be empty.');
        }

        return new self($id, $email, $displayName, $passwordHash, self::utc($now));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): EmailAddress
    {
        return new EmailAddress($this->email);
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return AccountStatus::Active === $this->status;
    }

    public function lastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function changePasswordHash(string $passwordHash, \DateTimeImmutable $now): void
    {
        if ('' === $passwordHash) {
            throw new \InvalidArgumentException('Password hash cannot be empty.');
        }
        $this->passwordHash = $passwordHash;
        $this->updatedAt = self::utc($now);
    }

    public function suspend(\DateTimeImmutable $now): void
    {
        $this->status = AccountStatus::Suspended;
        $this->updatedAt = self::utc($now);
    }

    public function reactivate(\DateTimeImmutable $now): void
    {
        $this->status = AccountStatus::Active;
        $this->updatedAt = self::utc($now);
    }

    public function recordLogin(\DateTimeImmutable $now): void
    {
        $this->lastLoginAt = self::utc($now);
    }

    private static function utc(\DateTimeImmutable $at): \DateTimeImmutable
    {
        return $at->setTimezone(new \DateTimeZone('UTC'));
    }
}
