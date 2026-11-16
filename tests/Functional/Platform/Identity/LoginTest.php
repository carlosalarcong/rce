<?php

declare(strict_types=1);

namespace App\Tests\Functional\Platform\Identity;

use App\Platform\Identity\Application\CreateAccount;
use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Inicio de sesión contra la base Main de pruebas (rce_main_test; make test-db). */
final class LoginTest extends WebTestCase
{
    private const string PASSWORD = 'una frase larga y segura';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        // IP al azar: los frenos de intentos (en Valkey) son por IP y no se mezclan entre tests ni corridas.
        $this->client->setServerParameter('REMOTE_ADDR', \sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254)));
        $db = self::getContainer()->get('doctrine.dbal.default_connection');
        $db->executeStatement('TRUNCATE user_account, password_reset_token');
    }

    public function testProtectedPageSendsToLogin(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/ingresar');
    }

    public function testLoginWithCorrectPasswordShowsTheHome(): void
    {
        $this->account('ana@clinica.cl');

        $this->login('ANA@clinica.cl ', self::PASSWORD);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Hola, Ana Pérez');
        self::assertNotNull($this->accounts()->byEmail(new EmailAddress('ana@clinica.cl'))?->lastLoginAt());
    }

    public function testWrongPasswordAndUnknownEmailGiveTheSameMessage(): void
    {
        $this->account('ana@clinica.cl');

        $this->login('ana@clinica.cl', 'no es la contraseña');
        $this->client->followRedirect();
        $wrongPassword = $this->client->getCrawler()->filter('.alert-error')->text();

        $this->login('nadie@clinica.cl', 'no es la contraseña');
        $this->client->followRedirect();
        $unknownEmail = $this->client->getCrawler()->filter('.alert-error')->text();

        self::assertSame('Correo o contraseña incorrectos.', $wrongPassword);
        self::assertSame($wrongPassword, $unknownEmail);
    }

    public function testSuspendedAccountCannotEnterButOnlyLearnsItWithTheRightPassword(): void
    {
        $id = $this->account('suspendida@clinica.cl');
        $account = $this->accounts()->byId($id);
        self::assertNotNull($account);
        $account->suspend(new \DateTimeImmutable());
        $this->accounts()->save($account);

        $this->login('suspendida@clinica.cl', 'no es la contraseña');
        $this->client->followRedirect();
        self::assertSelectorTextSame('.alert-error', 'Correo o contraseña incorrectos.');

        $this->login('suspendida@clinica.cl', self::PASSWORD);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-error', 'Tu cuenta está suspendida');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/ingresar');
    }

    public function testSixthFailedAttemptIsThrottled(): void
    {
        $this->account('ana@clinica.cl');
        for ($i = 0; $i < 5; ++$i) {
            $this->login('ana@clinica.cl', 'no es la contraseña');
        }
        $this->login('ana@clinica.cl', self::PASSWORD);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-error', 'Demasiados intentos');
    }

    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $this->account('ana@clinica.cl');

        $this->client->request('POST', '/ingresar', ['email' => 'ana@clinica.cl', 'password' => self::PASSWORD]);
        $this->client->followRedirect();

        self::assertSelectorExists('.alert-error');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/ingresar');
    }

    public function testLogoutNeedsItsCsrfToken(): void
    {
        $this->account('ana@clinica.cl');
        $this->login('ana@clinica.cl', self::PASSWORD);

        $this->client->request('POST', '/salir');
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful('Without its token, "Salir" must not close the session.');

        $this->client->submitForm('Salir');
        self::assertResponseRedirects('/ingresar');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/ingresar');
    }

    public function testLogoutByGetIsNotAllowed(): void
    {
        $this->account('ana@clinica.cl');
        $this->login('ana@clinica.cl', self::PASSWORD);

        $this->client->request('GET', '/salir');
        self::assertResponseStatusCodeSame(405);
    }

    private function login(string $email, string $password): void
    {
        $this->client->request('GET', '/ingresar');
        $this->client->submitForm('Entrar', ['email' => $email, 'password' => $password]);
    }

    private function account(string $email): string
    {
        $create = self::getContainer()->get(CreateAccount::class);

        return $create($email, 'Ana Pérez', self::PASSWORD);
    }

    private function accounts(): UserAccountRepositoryInterface
    {
        $repository = self::getContainer()->get(UserAccountRepositoryInterface::class);

        return $repository;
    }
}
