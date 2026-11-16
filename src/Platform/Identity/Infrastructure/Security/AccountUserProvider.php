<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Security;

use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Carga la cuenta por correo (al entrar) o por UUID (al recargar la sesión).
 *
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class AccountUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public function __construct(
        private UserAccountRepositoryInterface $accounts,
        private ClockInterface $clock,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (1 === preg_match(self::UUID_PATTERN, $identifier)) {
            $account = $this->accounts->byId($identifier);
        } else {
            try {
                $account = $this->accounts->byEmail(new EmailAddress($identifier));
            } catch (\InvalidArgumentException) {
                $account = null;
            }
        }

        if (null === $account) {
            $e = new UserNotFoundException();
            $e->setUserIdentifier($identifier);

            throw $e;
        }

        return SecurityUser::fromAccount($account);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }

    /** Si el algoritmo o su costo cambian, se vuelve a cifrar al entrar. */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SecurityUser) {
            return;
        }
        $account = $this->accounts->byId($user->getUserIdentifier());
        if (null !== $account) {
            $account->changePasswordHash($newHashedPassword, $this->clock->now());
            $this->accounts->save($account);
        }
    }
}
