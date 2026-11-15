<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Infrastructure\Hakam;

use Hakam\MultiTenancyBundle\Config\TenantConnectionConfigDTO;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * La clave con que la aplicación entra a las bases de los tenants (docs/adr/0003).
 *
 * Sale solo de TENANT_DB_PASSWORD, que en GCP viene de Secret Manager. Nunca de
 * la columna de clave de tenant_db_config: queda vacía y, si trae algo, se ignora.
 */
final readonly class TenantDbPassword
{
    public function __construct(
        #[\SensitiveParameter]
        #[Autowire('%env(TENANT_DB_PASSWORD)%')]
        private string $password,
    ) {
    }

    public function fill(TenantConnectionConfigDTO $connection): TenantConnectionConfigDTO
    {
        $connection->password = $this->password;

        return $connection;
    }

    /**
     * @param array<mixed> $connections
     *
     * @return array<mixed>
     */
    public function fillAll(array $connections): array
    {
        return array_map(
            fn (mixed $c): mixed => $c instanceof TenantConnectionConfigDTO ? $this->fill($c) : $c,
            $connections,
        );
    }
}
