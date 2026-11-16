<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Una cuenta suspendida no entra. Se revisa DESPUÉS de la contraseña: así un
 * desconocido no puede averiguar qué correos existen probando contraseñas al azar.
 */
final readonly class AccountUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if ($user instanceof SecurityUser && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Tu cuenta está suspendida. Pide ayuda al administrador de tu clínica.');
        }
    }
}
