<?php

declare(strict_types=1);

namespace App\Tests\Functional\Platform\Identity;

use App\Platform\Identity\Application\CreateAccount;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mime\Email;

/** "¿Olvidaste tu contraseña?" de punta a punta, con el correo atrapado por el mailer de pruebas. */
final class PasswordResetTest extends WebTestCase
{
    private const string PASSWORD = 'una frase larga y segura';
    private const string NEW_PASSWORD = 'otra frase larga distinta';

    private KernelBrowser $client;
    private Connection $db;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        // IP al azar: los frenos de intentos (en Valkey) son por IP y no se mezclan entre tests ni corridas.
        $this->client->setServerParameter('REMOTE_ADDR', \sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254)));
        $db = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->db = $db;
        $this->db->executeStatement('TRUNCATE user_account, password_reset_token');
        $create = self::getContainer()->get(CreateAccount::class);
        $create('ana@clinica.cl', 'Ana Pérez', self::PASSWORD);
    }

    public function testFullFlowChangesThePasswordAndTheLinkWorksOnce(): void
    {
        $link = $this->requestLink('ana@clinica.cl');

        $this->client->request('GET', $link);
        self::assertResponseRedirects('/recuperar-clave/nueva', null, 'The token must leave the URL.');
        $this->client->followRedirect();
        $this->client->submitForm('Guardar contraseña', [
            'reset_password[password][first]' => self::NEW_PASSWORD,
            'reset_password[password][second]' => self::NEW_PASSWORD,
        ]);
        self::assertResponseRedirects('/ingresar');

        $this->login(self::PASSWORD);
        $this->client->followRedirect();
        self::assertSelectorTextSame('.alert-error', 'Correo o contraseña incorrectos.');

        $this->login(self::NEW_PASSWORD);
        self::assertResponseRedirects('/');

        $this->client->request('GET', $link);
        $this->client->followRedirect();
        self::assertResponseStatusCodeSame(410);
        self::assertSelectorTextContains('h1', 'El enlace ya no sirve');
    }

    public function testUnknownEmailGetsTheSameAnswerAndNoEmail(): void
    {
        $this->client->request('GET', '/recuperar-clave');
        $this->client->submitForm('Enviar enlace', ['forgot_password[email]' => 'nadie@clinica.cl']);

        self::assertResponseRedirects('/recuperar-clave/enviado');
        self::assertEmailCount(0);
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Revisa tu correo');
    }

    public function testExpiredLinkIsRejected(): void
    {
        $link = $this->requestLink('ana@clinica.cl');
        $this->db->executeStatement("UPDATE password_reset_token SET expires_at = NOW() - INTERVAL '1 minute'");

        $this->client->request('GET', $link);
        $this->client->followRedirect();

        self::assertResponseStatusCodeSame(410);
    }

    public function testMismatchedOrWeakPasswordKeepsTheForm(): void
    {
        $this->client->request('GET', $this->requestLink('ana@clinica.cl'));
        $this->client->followRedirect();

        $this->client->submitForm('Guardar contraseña', [
            'reset_password[password][first]' => self::NEW_PASSWORD,
            'reset_password[password][second]' => 'otra cosa distinta',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Las dos contraseñas no coinciden.');

        $this->client->submitForm('Guardar contraseña', [
            'reset_password[password][first]' => 'corta',
            'reset_password[password][second]' => 'corta',
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'al menos 12 caracteres');
    }

    public function testRandomLinkIsRejected(): void
    {
        $this->client->request('GET', '/recuperar-clave/enlace/'.str_repeat('A', 43));
        $this->client->followRedirect();

        self::assertResponseStatusCodeSame(410);
    }

    public function testTheDatabaseNeverHoldsTheLink(): void
    {
        $link = $this->requestLink('ana@clinica.cl');
        $token = basename($link);

        $stored = $this->db->fetchOne('SELECT token_hash FROM password_reset_token');
        self::assertSame(hash('sha256', $token), $stored);
        self::assertStringNotContainsString($token, (string) json_encode($this->db->fetchAllAssociative('SELECT * FROM password_reset_token')));
    }

    private function requestLink(string $email): string
    {
        $this->client->request('GET', '/recuperar-clave');
        $this->client->submitForm('Enviar enlace', ['forgot_password[email]' => $email]);
        self::assertResponseRedirects('/recuperar-clave/enviado');
        self::assertEmailCount(1);

        $message = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertEmailAddressContains($message, 'To', 'ana@clinica.cl');
        self::assertEmailTextBodyContains($message, 'vence en 30 minutos');
        self::assertSame(1, preg_match('#https?://[^\s"]+/recuperar-clave/enlace/[A-Za-z0-9_-]+#', (string) $message->getTextBody(), $m));

        return (string) parse_url($m[0], \PHP_URL_PATH);
    }

    private function login(string $password): void
    {
        $this->client->request('GET', '/ingresar');
        $this->client->submitForm('Entrar', ['email' => 'ana@clinica.cl', 'password' => $password]);
    }
}
