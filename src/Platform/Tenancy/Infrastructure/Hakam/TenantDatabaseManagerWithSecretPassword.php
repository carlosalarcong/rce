<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Infrastructure\Hakam;

use Hakam\MultiTenancyBundle\Config\TenantConnectionConfigDTO;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Port\TenantDatabaseManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Los comandos de Hakam que crean y migran bases (tenant:database:create,
 * tenant:migrations:*) leen la conexión de aquí. Se envuelve para que la clave
 * salga de Secret Manager y nunca se guarde en tenant_db_config (docs/adr/0003).
 */
#[AsDecorator(TenantDatabaseManagerInterface::class)]
final readonly class TenantDatabaseManagerWithSecretPassword implements TenantDatabaseManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private TenantDatabaseManagerInterface $inner,
        private TenantDbPassword $password,
    ) {
    }

    /** @return array<mixed> */
    public function listDatabases(): array
    {
        return $this->password->fillAll($this->inner->listDatabases());
    }

    /** @return array<mixed> */
    public function listMissingDatabases(): array
    {
        return $this->password->fillAll($this->inner->listMissingDatabases());
    }

    public function getTenantDatabaseById(mixed $identifier): TenantConnectionConfigDTO
    {
        return $this->password->fill($this->inner->getTenantDatabaseById($identifier));
    }

    /** @return array<mixed> */
    public function getTenantDbListByDatabaseStatus(DatabaseStatusEnum $status): array
    {
        return $this->password->fillAll($this->inner->getTenantDbListByDatabaseStatus($status));
    }

    public function getDefaultTenantIDatabase(): TenantConnectionConfigDTO
    {
        return $this->password->fill($this->inner->getDefaultTenantIDatabase());
    }

    public function createTenantDatabase(TenantConnectionConfigDTO $tenantConnectionConfigDTO): bool
    {
        return $this->inner->createTenantDatabase($this->password->fill($tenantConnectionConfigDTO));
    }

    public function addNewTenantDbConfig(TenantConnectionConfigDTO $dto): TenantConnectionConfigDTO
    {
        // Se guarda sin clave; la copia que vuelve la trae desde Secret Manager.
        $withoutPassword = TenantConnectionConfigDTO::fromArgs(
            $dto->identifier, $dto->driver, $dto->dbStatus, $dto->host, $dto->port, $dto->dbname, $dto->user, null,
        );

        return $this->password->fill($this->inner->addNewTenantDbConfig($withoutPassword));
    }

    public function updateTenantDatabaseStatus(mixed $identifier, DatabaseStatusEnum $status): bool
    {
        return $this->inner->updateTenantDatabaseStatus($identifier, $status);
    }
}
