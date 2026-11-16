<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Security;

use App\Platform\Identity\Domain\UserAccount;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * La cuenta, vista por Symfony Security. El identificador es el UUID (el correo
 * solo sirve para entrar). Guarda la contraseña cifrada para que Symfony cierre
 * las demás sesiones cuando cambia.
 */
final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private function __construct(
        private string $id,
        private string $email,
        private string $displayName,
        private string $passwordHash,
        private bool $active,
    ) {
    }

    public static function fromAccount(UserAccount $account): self
    {
        return new self($account->id(), $account->email()->value, $account->displayName(), $account->passwordHash(), $account->isActive());
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->id) {
            throw new \LogicException('Account without id.');
        }

        return $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * En la sesión no se guarda la contraseña cifrada, solo una huella de ella:
     * basta para notar que cambió y cerrar la sesión.
     *
     * @return array<string, string|bool>
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'displayName' => $this->displayName,
            'passwordHash' => hash('crc32c', $this->passwordHash),
            'active' => $this->active,
        ];
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        foreach (['id', 'email', 'displayName', 'passwordHash'] as $key) {
            $this->{$key} = \is_string($data[$key] ?? null) ? $data[$key] : '';
        }
        $this->active = true === ($data['active'] ?? false);
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
