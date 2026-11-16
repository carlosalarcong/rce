<?php

declare(strict_types=1);

namespace App\Tests\Functional\Platform\Identity;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateAccountCommandTest extends KernelTestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        self::bootKernel();
        $db = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->db = $db;
        $this->db->executeStatement('TRUNCATE user_account, password_reset_token');
    }

    public function testCreatesAccountAskingThePasswordTwice(): void
    {
        $tester = $this->tester();
        $tester->setInputs(['una frase larga y segura', 'una frase larga y segura']);

        $status = $tester->execute(['email' => 'Carlos@RCE.cl', '--nombre' => 'Carlos']);

        self::assertSame(Command::SUCCESS, $status);
        self::assertStringContainsString('carlos@rce.cl', $tester->getDisplay());
        self::assertSame('Carlos', $this->db->fetchOne("SELECT display_name FROM user_account WHERE email = 'carlos@rce.cl'"));
        $hash = $this->db->fetchOne('SELECT password_hash FROM user_account');
        self::assertIsString($hash);
        self::assertStringNotContainsString('una frase larga y segura', $hash);
    }

    public function testRejectsMismatchWeakPasswordAndDuplicates(): void
    {
        $tester = $this->tester();

        $tester->setInputs(['una frase larga y segura', 'otra distinta y larga']);
        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'a@rce.cl', '--nombre' => 'A']));
        self::assertStringContainsString('no coinciden', $tester->getDisplay());

        $tester->setInputs(['corta', 'corta']);
        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'a@rce.cl', '--nombre' => 'A']));
        self::assertStringContainsString('al menos 12', $tester->getDisplay());

        $tester->setInputs(['una frase larga y segura', 'una frase larga y segura']);
        self::assertSame(Command::SUCCESS, $tester->execute(['email' => 'a@rce.cl', '--nombre' => 'A']));
        $tester->setInputs(['una frase larga y segura', 'una frase larga y segura']);
        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'A@rce.cl', '--nombre' => 'A']));
        self::assertStringContainsString('Ya existe', $tester->getDisplay());
        self::assertEquals(1, $this->db->fetchOne('SELECT count(*) FROM user_account'));
    }

    private function tester(): CommandTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        return new CommandTester(new Application($kernel)->find('rce:identity:create-account'));
    }
}
