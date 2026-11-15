<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Tenancy\Infrastructure\Hakam;

use App\Platform\Tenancy\Infrastructure\Hakam\TenantConfigProviderWithSecretPassword;
use App\Platform\Tenancy\Infrastructure\Hakam\TenantDatabaseManagerWithSecretPassword;
use App\Platform\Tenancy\Infrastructure\Hakam\TenantDbPassword;
use Hakam\MultiTenancyBundle\Config\TenantConnectionConfigDTO;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use Hakam\MultiTenancyBundle\Port\TenantConfigProviderInterface;
use Hakam\MultiTenancyBundle\Port\TenantDatabaseManagerInterface;
use PHPUnit\Framework\TestCase;

final class TenantSecretPasswordDecoratorsTest extends TestCase
{
    private const string SECRET = 'clave-de-secret-manager';

    public function testProviderReplacesWhateverPasswordComesFromTheRegistry(): void
    {
        $inner = $this->createStub(TenantConfigProviderInterface::class);
        $inner->method('getTenantConnectionConfig')->willReturn($this->dto(password: 'clave-vieja-en-main'));

        $provider = new TenantConfigProviderWithSecretPassword($inner, new TenantDbPassword(self::SECRET));

        self::assertSame(self::SECRET, $provider->getTenantConnectionConfig('centro-demo')->password);
    }

    public function testProviderFillsPasswordWhenRegistryHasNone(): void
    {
        $inner = $this->createStub(TenantConfigProviderInterface::class);
        $inner->method('getTenantConnectionConfig')->willReturn($this->dto(password: null));

        $provider = new TenantConfigProviderWithSecretPassword($inner, new TenantDbPassword(self::SECRET));

        self::assertSame(self::SECRET, $provider->getTenantConnectionConfig('centro-demo')->password);
    }

    public function testManagerFillsPasswordInEveryListedConnection(): void
    {
        $inner = $this->createStub(TenantDatabaseManagerInterface::class);
        $inner->method('listDatabases')->willReturn([$this->dto(), $this->dto()]);
        $inner->method('listMissingDatabases')->willReturn([$this->dto()]);
        $inner->method('getTenantDbListByDatabaseStatus')->willReturn([$this->dto()]);

        $manager = $this->manager($inner);

        foreach ([
            ...$manager->listDatabases(),
            ...$manager->listMissingDatabases(),
            ...$manager->getTenantDbListByDatabaseStatus(DatabaseStatusEnum::DATABASE_CREATED),
        ] as $connection) {
            self::assertInstanceOf(TenantConnectionConfigDTO::class, $connection);
            self::assertSame(self::SECRET, $connection->password);
        }
    }

    public function testManagerFillsPasswordForSingleConnections(): void
    {
        $inner = $this->createStub(TenantDatabaseManagerInterface::class);
        $inner->method('getTenantDatabaseById')->willReturn($this->dto());
        $inner->method('getDefaultTenantIDatabase')->willReturn($this->dto());

        $manager = $this->manager($inner);

        self::assertSame(self::SECRET, $manager->getTenantDatabaseById('centro-demo')->password);
        self::assertSame(self::SECRET, $manager->getDefaultTenantIDatabase()->password);
    }

    public function testManagerCreatesDatabaseWithSecretPassword(): void
    {
        $inner = $this->createMock(TenantDatabaseManagerInterface::class);
        $inner->expects(self::once())
            ->method('createTenantDatabase')
            ->with(self::callback(static fn (TenantConnectionConfigDTO $c): bool => self::SECRET === $c->password))
            ->willReturn(true);

        self::assertTrue($this->manager($inner)->createTenantDatabase($this->dto()));
    }

    public function testManagerNeverStoresThePasswordWhenRegisteringATenant(): void
    {
        $inner = $this->createMock(TenantDatabaseManagerInterface::class);
        $inner->expects(self::once())
            ->method('addNewTenantDbConfig')
            ->with(self::callback(static fn (TenantConnectionConfigDTO $c): bool => null === $c->password))
            ->willReturnArgument(0);

        $stored = $this->manager($inner)->addNewTenantDbConfig($this->dto(password: 'no-guardar'));

        self::assertSame(self::SECRET, $stored->password);
    }

    public function testManagerPassesThroughNonConnectionItems(): void
    {
        $inner = $this->createStub(TenantDatabaseManagerInterface::class);
        $inner->method('listDatabases')->willReturn(['algo-raro']);

        self::assertSame(['algo-raro'], $this->manager($inner)->listDatabases());
    }

    private function manager(TenantDatabaseManagerInterface $inner): TenantDatabaseManagerWithSecretPassword
    {
        return new TenantDatabaseManagerWithSecretPassword($inner, new TenantDbPassword(self::SECRET));
    }

    private function dto(?string $password = null): TenantConnectionConfigDTO
    {
        return TenantConnectionConfigDTO::fromArgs(
            identifier: 'centro-demo',
            driver: DriverTypeEnum::POSTGRES,
            dbStatus: DatabaseStatusEnum::DATABASE_CREATED,
            host: 'database',
            port: 5432,
            dbname: 'rce_tenant_demo',
            user: 'rce_app',
            password: $password,
        );
    }
}
