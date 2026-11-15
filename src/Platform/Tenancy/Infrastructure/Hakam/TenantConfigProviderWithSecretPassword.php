<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Infrastructure\Hakam;

use Hakam\MultiTenancyBundle\Config\TenantConnectionConfigDTO;
use Hakam\MultiTenancyBundle\Port\TenantConfigProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Hakam conecta la base del tenant en cada request con lo que entrega este
 * servicio. Se envuelve para que la clave salga de Secret Manager (docs/adr/0003).
 */
#[AsDecorator(TenantConfigProviderInterface::class)]
final readonly class TenantConfigProviderWithSecretPassword implements TenantConfigProviderInterface
{
    public function __construct(
        #[AutowireDecorated]
        private TenantConfigProviderInterface $inner,
        private TenantDbPassword $password,
    ) {
    }

    public function getTenantConnectionConfig(mixed $identifier): TenantConnectionConfigDTO
    {
        return $this->password->fill($this->inner->getTenantConnectionConfig($identifier));
    }
}
