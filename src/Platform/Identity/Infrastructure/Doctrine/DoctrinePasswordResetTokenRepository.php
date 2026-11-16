<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Doctrine;

use App\Platform\Identity\Domain\PasswordResetToken;
use App\Platform\Identity\Domain\PasswordResetTokenRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PasswordResetTokenRepositoryInterface::class)]
final readonly class DoctrinePasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    public function __construct(private EntityManagerInterface $main)
    {
    }

    public function byHash(string $tokenHash): ?PasswordResetToken
    {
        return $this->main->getRepository(PasswordResetToken::class)->findOneBy(['tokenHash' => $tokenHash]);
    }

    public function save(PasswordResetToken $token): void
    {
        $this->main->persist($token);
        $this->main->flush();
    }

    public function invalidateAllFor(string $accountId, \DateTimeImmutable $now): void
    {
        $this->main->createQueryBuilder()
            ->update(PasswordResetToken::class, 't')
            ->set('t.usedAt', ':now')
            ->where('t.accountId = :account')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('now', $now->setTimezone(new \DateTimeZone('UTC')), 'datetime_immutable')
            ->setParameter('account', $accountId)
            ->getQuery()
            ->execute();
    }
}
