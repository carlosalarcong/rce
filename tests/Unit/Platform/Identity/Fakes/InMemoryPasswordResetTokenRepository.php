<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Identity\Fakes;

use App\Platform\Identity\Domain\PasswordResetToken;
use App\Platform\Identity\Domain\PasswordResetTokenRepositoryInterface;

final class InMemoryPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    /** @var array<string, PasswordResetToken> */
    public array $tokens = [];

    public function byHash(string $tokenHash): ?PasswordResetToken
    {
        foreach ($this->tokens as $token) {
            if ($token->tokenHash() === $tokenHash) {
                return $token;
            }
        }

        return null;
    }

    public function save(PasswordResetToken $token): void
    {
        $this->tokens[$token->id()] = $token;
    }

    public function invalidateAllFor(string $accountId, \DateTimeImmutable $now): void
    {
        foreach ($this->tokens as $token) {
            if ($token->accountId() === $accountId && $token->isUsable($now)) {
                $token->markUsed($now);
            }
        }
    }
}
