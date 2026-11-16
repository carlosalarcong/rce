<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\PasswordResetToken;
use App\Platform\Identity\Domain\PasswordResetTokenRepositoryInterface;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/**
 * "Olvidé mi contraseña". No dice si el correo existe: quien llama responde
 * siempre lo mismo. Solo las cuentas activas reciben el enlace.
 */
final readonly class RequestPasswordReset
{
    public function __construct(
        private UserAccountRepositoryInterface $accounts,
        private PasswordResetTokenRepositoryInterface $tokens,
        private PasswordResetMailerInterface $mailer,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(string $email): void
    {
        try {
            $address = new EmailAddress($email);
        } catch (\InvalidArgumentException) {
            return;
        }

        $account = $this->accounts->byEmail($address);
        if (null === $account || !$account->isActive()) {
            return;
        }

        $now = $this->clock->now();
        $this->tokens->invalidateAllFor($account->id(), $now);

        $plainToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $token = PasswordResetToken::issue(Uuid::v7()->toRfc4122(), $account->id(), $plainToken, $now);
        $this->tokens->save($token);

        $this->mailer->send($account, $plainToken, $token->expiresAt());
    }
}
