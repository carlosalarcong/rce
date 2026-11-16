<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Identity;

use App\Platform\Identity\Application\CreateAccount;
use App\Platform\Identity\Application\EmailAlreadyRegisteredException;
use App\Platform\Identity\Application\InvalidResetTokenException;
use App\Platform\Identity\Application\PasswordPolicy;
use App\Platform\Identity\Application\RequestPasswordReset;
use App\Platform\Identity\Application\ResetPassword;
use App\Platform\Identity\Application\WeakPasswordException;
use App\Tests\Unit\Platform\Identity\Fakes\FakePasswordHasher;
use App\Tests\Unit\Platform\Identity\Fakes\InMemoryPasswordResetTokenRepository;
use App\Tests\Unit\Platform\Identity\Fakes\InMemoryUserAccountRepository;
use App\Tests\Unit\Platform\Identity\Fakes\RecordingPasswordResetMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class IdentityUseCasesTest extends TestCase
{
    private const string PASSWORD = 'una frase larga y segura';

    private InMemoryUserAccountRepository $accounts;
    private InMemoryPasswordResetTokenRepository $tokens;
    private RecordingPasswordResetMailer $mailer;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->accounts = new InMemoryUserAccountRepository();
        $this->tokens = new InMemoryPasswordResetTokenRepository();
        $this->mailer = new RecordingPasswordResetMailer();
        $this->clock = new MockClock('2026-11-16 12:00:00 UTC');
    }

    public function testCreatesAccountWithHashedPassword(): void
    {
        $id = $this->create('Ana@Clinica.cl');

        $account = $this->accounts->byId($id);
        self::assertNotNull($account);
        self::assertSame('ana@clinica.cl', $account->email()->value);
        self::assertNotSame(self::PASSWORD, $account->passwordHash());
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
    }

    public function testEmailCanOnlyBeUsedOnce(): void
    {
        $this->create('ana@clinica.cl');

        $this->expectException(EmailAlreadyRegisteredException::class);
        $this->create('ANA@clinica.cl');
    }

    public function testPasswordPolicy(): void
    {
        $policy = new PasswordPolicy();
        $policy->assertAcceptable(self::PASSWORD, 'ana@clinica.cl');
        // Una parte local corta no invalida contraseñas que contengan esas letras.
        $policy->assertAcceptable('una frase larga y segura', 'a@clinica.cl');

        foreach (['corta', str_repeat(' ', 20), 'mi correo es anaperez123', str_repeat('x', 4097)] as $bad) {
            try {
                $policy->assertAcceptable($bad, 'anaperez@clinica.cl');
                self::fail('Expected a weak password: '.$bad);
            } catch (WeakPasswordException $e) {
                self::assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testResetRequestForUnknownOrSuspendedEmailSendsNothing(): void
    {
        $this->request()('nadie@clinica.cl');
        $this->request()('no es un correo');

        $id = $this->create('suspendida@clinica.cl');
        $this->accounts->byId($id)?->suspend($this->clock->now());
        $this->request()('suspendida@clinica.cl');

        self::assertSame(0, $this->mailer->count());
        self::assertSame([], $this->tokens->tokens);
    }

    public function testResetFlowChangesPasswordAndTheLinkWorksOnlyOnce(): void
    {
        $id = $this->create('ana@clinica.cl');
        $oldHash = $this->accounts->byId($id)?->passwordHash();

        $this->request()('ana@clinica.cl');
        self::assertSame(1, $this->mailer->count());
        $plain = $this->mailer->sent[0]['token'];
        self::assertGreaterThanOrEqual(43, \strlen($plain));
        self::assertEquals($this->clock->now()->modify('+30 minutes'), $this->mailer->sent[0]['expiresAt']);

        $reset = $this->reset();
        self::assertTrue($reset->isValid($plain));
        $reset($plain, 'otra frase larga distinta');

        self::assertNotSame($oldHash, $this->accounts->byId($id)?->passwordHash());
        self::assertFalse($reset->isValid($plain));
        $this->expectException(InvalidResetTokenException::class);
        $reset($plain, 'una tercera frase larga');
    }

    public function testOnlyTheNewestLinkWorks(): void
    {
        $this->create('ana@clinica.cl');
        $this->request()('ana@clinica.cl');
        $this->request()('ana@clinica.cl');

        self::assertFalse($this->reset()->isValid($this->mailer->sent[0]['token']));
        self::assertTrue($this->reset()->isValid($this->mailer->sent[1]['token']));
    }

    public function testExpiredLinkIsRejected(): void
    {
        $this->create('ana@clinica.cl');
        $this->request()('ana@clinica.cl');
        $this->clock->sleep(30 * 60);

        $this->expectException(InvalidResetTokenException::class);
        $this->reset()($this->mailer->sent[0]['token'], 'otra frase larga distinta');
    }

    public function testLinkOfAnAccountSuspendedAfterwardsIsRejected(): void
    {
        $id = $this->create('ana@clinica.cl');
        $this->request()('ana@clinica.cl');
        $this->accounts->byId($id)?->suspend($this->clock->now());

        self::assertFalse($this->reset()->isValid($this->mailer->sent[0]['token']));
    }

    public function testWeakNewPasswordKeepsTheLinkUsable(): void
    {
        $this->create('ana@clinica.cl');
        $this->request()('ana@clinica.cl');
        $plain = $this->mailer->sent[0]['token'];

        try {
            $this->reset()($plain, 'corta');
            self::fail('Expected a weak password.');
        } catch (WeakPasswordException) {
        }

        self::assertTrue($this->reset()->isValid($plain));
    }

    private function create(string $email): string
    {
        return new CreateAccount($this->accounts, new FakePasswordHasher(), new PasswordPolicy(), $this->clock)($email, 'Ana Pérez', self::PASSWORD);
    }

    private function request(): RequestPasswordReset
    {
        return new RequestPasswordReset($this->accounts, $this->tokens, $this->mailer, $this->clock);
    }

    private function reset(): ResetPassword
    {
        return new ResetPassword($this->accounts, $this->tokens, new FakePasswordHasher(), new PasswordPolicy(), $this->clock);
    }
}
