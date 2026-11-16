<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Doctrine;

use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\UserAccount;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/** Cuentas en la base Main (EM por defecto), nunca en la base de una clínica. */
#[AsAlias(UserAccountRepositoryInterface::class)]
final readonly class DoctrineUserAccountRepository implements UserAccountRepositoryInterface
{
    public function __construct(private EntityManagerInterface $main)
    {
    }

    public function byId(string $id): ?UserAccount
    {
        if (1 !== preg_match('/^[0-9a-f-]{36}$/', $id)) {
            return null;
        }

        return $this->main->find(UserAccount::class, $id);
    }

    public function byEmail(EmailAddress $email): ?UserAccount
    {
        return $this->main->getRepository(UserAccount::class)->findOneBy(['email' => $email->value]);
    }

    public function save(UserAccount $account): void
    {
        $this->main->persist($account);
        $this->main->flush();
    }
}
