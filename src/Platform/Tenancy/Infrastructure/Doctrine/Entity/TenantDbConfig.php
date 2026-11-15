<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Infrastructure\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Hakam\MultiTenancyBundle\Services\TenantDbConfigurationInterface;
use Hakam\MultiTenancyBundle\Traits\TenantDbConfigTrait;

/**
 * Registro de un tenant (centro de salud) en la base Main.
 *
 * Es infraestructura, no dominio: existe para que Hakam sepa a qué base
 * conectarse. Vive en Main y nunca en la base del tenant.
 *
 * Host, puerto y usuario de la base se guardan aquí (no son secretos). La clave
 * NUNCA: su columna queda vacía y la pone TenantDbPassword desde Secret Manager
 * (docs/adr/0003).
 */
#[ORM\Entity]
#[ORM\Table(name: 'tenant_db_config')]
class TenantDbConfig implements TenantDbConfigurationInterface
{
    use TenantDbConfigTrait;

    /** Etiqueta DNS: minúsculas, dígitos y guion; sin guion al inicio ni al final. */
    private const string SUBDOMAIN_PATTERN = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 63, unique: true)]
    private string $subdomain;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $subdomain,
        string $name,
        string $dbName,
        string $dbHost,
        int $dbPort,
        string $dbUser,
        \DateTimeImmutable $createdAt,
    ) {
        if (1 !== preg_match(self::SUBDOMAIN_PATTERN, $subdomain)) {
            throw new \InvalidArgumentException(\sprintf('Invalid tenant subdomain "%s".', $subdomain));
        }

        if ('' === trim($name)) {
            throw new \InvalidArgumentException('Tenant name cannot be empty.');
        }

        if ($dbPort < 1 || $dbPort > 65535) {
            throw new \InvalidArgumentException(\sprintf('Invalid database port %d.', $dbPort));
        }

        $this->subdomain = $subdomain;
        $this->name = $name;
        $this->dbName = $dbName;
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
        $this->dbUserName = $dbUser;
        $this->driverType = DriverTypeEnum::POSTGRES;
        $this->createdAt = $createdAt->setTimezone(new \DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * El trait define getDbUserName() y la interfaz getDbUsername(): en PHP son
     * el mismo método. Se accede a la propiedad directamente para no recursar.
     */
    public function getDbUsername(): ?string
    {
        return $this->dbUserName;
    }

    /** Valor de la columna configurada en tenant_database_identifier. */
    public function getIdentifierValue(): string
    {
        return $this->subdomain;
    }
}
