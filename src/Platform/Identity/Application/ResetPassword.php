<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

use App\Platform\Identity\Domain\PasswordResetToken;
use App\Platform\Identity\Domain\PasswordResetTokenRepositoryInterface;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Psr\Clock\ClockInterface;

/**
 * Elige la contraseña nueva con un enlace válido. Al cambiar la contraseña,
 * Symfony cierra las demás sesiones abiertas de esa cuenta (su huella cambia).
 */
final readonly class ResetPassword
{
    public function __construct(
        private UserAccountRepositoryInterface $accounts,
        private PasswordResetTokenRepositoryInterface $tokens,
        private PasswordHasherInterface $hasher,
        private PasswordPolicy $policy,
        private ClockInterface $clock,
    ) {
    }

    /** El enlace sirve: existe, no venció, no se usó y la cuenta está activa. */
    public function isValid(#[\SensitiveParameter] string $plainToken): bool
    {
        $token = $this->tokens->byHash(PasswordResetToken::hash($plainToken));
        if (null === $token || !$token->isUsable($this->clock->now())) {
            return false;
        }

        return $this->accounts->byId($token->accountId())?->isActive() ?? false;
    }

    /**
     * @throws InvalidResetTokenException
     * @throws WeakPasswordException
     */
    public function __invoke(#[\SensitiveParameter] string $plainToken, #[\SensitiveParameter] string $newPassword): void
    {
        $now = $this->clock->now();
        $token = $this->tokens->byHash(PasswordResetToken::hash($plainToken));
        $account = null === $token ? null : $this->accounts->byId($token->accountId());
        if (null === $token || null === $account || !$token->isUsable($now) || !$account->isActive()) {
            throw new InvalidResetTokenException('El enlace no es válido o ya venció.');
        }

        $this->policy->assertAcceptable($newPassword, $account->email()->value);

        $account->changePasswordHash($this->hasher->hash($newPassword), $now);
        $token->markUsed($now);
        $this->tokens->invalidateAllFor($account->id(), $now);
        $this->accounts->save($account);
        $this->tokens->save($token);
    }
}
