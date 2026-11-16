<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Identity\Fakes;

use App\Platform\Identity\Application\PasswordResetMailerInterface;
use App\Platform\Identity\Domain\UserAccount;

final class RecordingPasswordResetMailer implements PasswordResetMailerInterface
{
    /** @var list<array{email: string, token: string, expiresAt: \DateTimeImmutable}> */
    public array $sent = [];

    public function send(UserAccount $account, #[\SensitiveParameter] string $plainToken, \DateTimeImmutable $expiresAt): void
    {
        $this->sent[] = ['email' => $account->email()->value, 'token' => $plainToken, 'expiresAt' => $expiresAt];
    }

    public function count(): int
    {
        return \count($this->sent);
    }
}
