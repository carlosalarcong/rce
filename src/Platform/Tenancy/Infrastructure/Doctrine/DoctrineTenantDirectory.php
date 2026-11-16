<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Infrastructure\Doctrine;

use App\Platform\Tenancy\Application\TenantDirectoryInterface;
use App\Platform\Tenancy\Infrastructure\Doctrine\Entity\TenantDbConfig;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/** Lee el registro de tenants en Main (EM por defecto). */
#[AsAlias(TenantDirectoryInterface::class)]
final readonly class DoctrineTenantDirectory implements TenantDirectoryInterface
{
    public function __construct(private EntityManagerInterface $mainEntityManager)
    {
    }

    public function identifiers(): array
    {
        /** @var list<string> $rows */
        $rows = $this->mainEntityManager->createQueryBuilder()
            ->select('t.subdomain')
            ->from(TenantDbConfig::class, 't')
            ->orderBy('t.subdomain')
            ->getQuery()
            ->getSingleColumnResult();

        return $rows;
    }
}
