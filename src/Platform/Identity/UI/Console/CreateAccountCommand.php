<?php

declare(strict_types=1);

namespace App\Platform\Identity\UI\Console;

use App\Platform\Identity\Application\CreateAccount;
use App\Platform\Identity\Application\EmailAlreadyRegisteredException;
use App\Platform\Identity\Application\WeakPasswordException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Crea una cuenta. La contraseña se pide sin mostrarla y nunca va en la línea de comandos. */
#[AsCommand(name: 'rce:identity:create-account', description: 'Crea una cuenta para entrar al RCE')]
final readonly class CreateAccountCommand
{
    public function __construct(private CreateAccount $createAccount)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Correo de la persona')] string $email,
        #[Option(description: 'Nombre que se muestra')] ?string $nombre = null,
    ): int {
        $nombre ??= self::text($io->ask('Nombre que se muestra'));
        $password = self::text($io->askHidden('Contraseña (no se muestra)'));
        $repeat = self::text($io->askHidden('Repite la contraseña'));
        if ($password !== $repeat) {
            $io->error('Las dos contraseñas no coinciden.');

            return Command::FAILURE;
        }

        try {
            $id = ($this->createAccount)($email, $nombre, $password);
        } catch (EmailAlreadyRegisteredException|WeakPasswordException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (\InvalidArgumentException) {
            $io->error('El correo o el nombre no son válidos.');

            return Command::FAILURE;
        }

        $io->success(\sprintf('Cuenta creada (%s). Ya puedes entrar con %s.', $id, mb_strtolower(trim($email))));

        return Command::SUCCESS;
    }

    private static function text(mixed $answer): string
    {
        return \is_string($answer) ? $answer : '';
    }
}
