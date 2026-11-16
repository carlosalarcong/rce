<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Identity;

use App\Platform\Identity\Domain\AccountStatus;
use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\PasswordResetToken;
use App\Platform\Identity\Domain\UserAccount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentityDomainTest extends TestCase
{
    private const string ID = '0193e0a0-0000-7000-8000-000000000001';

    public function testEmailIsNormalized(): void
    {
        self::assertSame('ana.perez@clinica.cl', new EmailAddress('  Ana.Perez@Clinica.CL ')->value);
        self::assertTrue(new EmailAddress('a@b.cl')->equals(new EmailAddress('A@B.CL')));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidEmails(): iterable
    {
        yield 'vacío' => [''];
        yield 'sin arroba' => ['ana.clinica.cl'];
        yield 'con espacios dentro' => ['ana perez@clinica.cl'];
        yield 'demasiado largo' => [str_repeat('a', 175).'@x.cl'];
    }

    #[DataProvider('invalidEmails')]
    public function testInvalidEmailsAreRejected(string $email): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EmailAddress($email);
    }

    public function testNewAccountIsActiveAndStoresUtc(): void
    {
        $account = UserAccount::create(self::ID, new EmailAddress('ana@clinica.cl'), ' Ana Pérez ', 'hash', new \DateTimeImmutable('2026-11-16 09:00:00', new \DateTimeZone('America/Santiago')));

        self::assertTrue($account->isActive());
        self::assertSame(AccountStatus::Active, $account->status());
        self::assertSame('Ana Pérez', $account->displayName());
        self::assertNull($account->lastLoginAt());

        $account->recordLogin(new \DateTimeImmutable('2026-11-16 10:00:00', new \DateTimeZone('America/Santiago')));
        self::assertSame('UTC', $account->lastLoginAt()?->getTimezone()->getName());
    }

    public function testSuspendedAccountIsNotActiveAndCanBeReactivated(): void
    {
        $account = $this->account();
        $account->suspend(new \DateTimeImmutable());
        self::assertFalse($account->isActive());
        self::assertSame('Suspendida', $account->status()->label());

        $account->reactivate(new \DateTimeImmutable());
        self::assertTrue($account->isActive());
    }

    public function testAccountRequiresUuidNameAndHash(): void
    {
        foreach ([['not-a-uuid', 'Ana', 'hash'], [self::ID, '   ', 'hash'], [self::ID, 'Ana', '']] as [$id, $name, $hash]) {
            try {
                UserAccount::create($id, new EmailAddress('ana@clinica.cl'), $name, $hash, new \DateTimeImmutable());
                self::fail(\sprintf('Expected rejection for [%s, %s, %s].', $id, $name, $hash));
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testResetTokenStoresOnlyItsHashAndExpiresIn30Minutes(): void
    {
        $plain = str_repeat('x', 43);
        $now = new \DateTimeImmutable('2026-11-16 12:00:00 UTC');
        $token = PasswordResetToken::issue('0193e0a0-0000-7000-8000-000000000002', self::ID, $plain, $now);

        self::assertSame(hash('sha256', $plain), $token->tokenHash());
        self::assertStringNotContainsString($plain, serialize($token));
        self::assertEquals($now->modify('+30 minutes'), $token->expiresAt());
        self::assertTrue($token->isUsable($now->modify('+29 minutes')));
        self::assertFalse($token->isUsable($now->modify('+30 minutes')));
    }

    public function testResetTokenWorksOnlyOnce(): void
    {
        $now = new \DateTimeImmutable();
        $token = PasswordResetToken::issue('0193e0a0-0000-7000-8000-000000000002', self::ID, str_repeat('x', 43), $now);
        $token->markUsed($now);

        self::assertFalse($token->isUsable($now));
        $this->expectException(\LogicException::class);
        $token->markUsed($now);
    }

    public function testShortResetTokenIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PasswordResetToken::issue('0193e0a0-0000-7000-8000-000000000002', self::ID, 'corto', new \DateTimeImmutable());
    }

    private function account(): UserAccount
    {
        return UserAccount::create(self::ID, new EmailAddress('ana@clinica.cl'), 'Ana', 'hash', new \DateTimeImmutable());
    }
}
