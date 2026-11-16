<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Security;

use App\Platform\Identity\Application\PasswordHasherInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/** Usa el mismo algoritmo que el inicio de sesión (security.yaml: argon2id en "auto"). */
#[AsAlias(PasswordHasherInterface::class)]
final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private PasswordHasherFactoryInterface $factory)
    {
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->factory->getPasswordHasher(SecurityUser::class)->hash($plainPassword);
    }
}
