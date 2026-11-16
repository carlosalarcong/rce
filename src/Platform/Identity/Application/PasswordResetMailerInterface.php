<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

use App\Platform\Identity\Domain\UserAccount;

/** Envía el correo con el enlace para elegir una contraseña nueva. */
interface PasswordResetMailerInterface
{
    public function send(UserAccount $account, #[\SensitiveParameter] string $plainToken, \DateTimeImmutable $expiresAt): void;
}
