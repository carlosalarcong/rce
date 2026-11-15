<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Tenancy\Infrastructure\Doctrine\Entity;

use App\Platform\Tenancy\Infrastructure\Doctrine\Entity\TenantDbConfig;
use Hakam\MultiTenancyBundle\Enum\DatabaseStatusEnum;
use Hakam\MultiTenancyBundle\Enum\DriverTypeEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TenantDbConfigTest extends TestCase
{
    public function testCreatesPostgresTenantIdentifiedBySubdomain(): void
    {
        $tenant = $this->tenant('centro-demo');

        self::assertSame('centro-demo', $tenant->getSubdomain());
        self::assertSame('centro-demo', $tenant->getIdentifierValue());
        self::assertSame('Centro Demo', $tenant->getName());
        self::assertSame('rce_tenant_demo', $tenant->getDbName());
        self::assertSame(DriverTypeEnum::POSTGRES, $tenant->getDriverType());
        self::assertSame(DatabaseStatusEnum::DATABASE_NOT_CREATED, $tenant->getDatabaseStatus());
    }

    public function testStoresConnectionDataButNeverThePassword(): void
    {
        $tenant = $this->tenant('centro-demo');

        self::assertSame('database', $tenant->getDbHost());
        self::assertSame(5432, $tenant->getDbPort());
        self::assertSame('rce_app', $tenant->getDbUsername());
        self::assertNull($tenant->getDbPassword());
    }

    /** @return iterable<string, array{int}> */
    public static function invalidPorts(): iterable
    {
        yield 'cero' => [0];
        yield 'negativo' => [-1];
        yield 'sobre 65535' => [65536];
    }

    #[DataProvider('invalidPorts')]
    public function testRejectsInvalidPort(int $port): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TenantDbConfig('centro-demo', 'Centro Demo', 'rce_tenant_demo', 'database', $port, 'rce_app', new \DateTimeImmutable());
    }

    public function testStoresCreationDateInUtc(): void
    {
        $tenant = new TenantDbConfig(
            'centro-demo',
            'Centro Demo',
            'rce_tenant_demo',
            'database',
            5432,
            'rce_app',
            new \DateTimeImmutable('2026-03-01 10:00:00', new \DateTimeZone('America/Santiago')),
        );

        self::assertSame('UTC', $tenant->getCreatedAt()->getTimezone()->getName());
        self::assertSame('2026-03-01 13:00:00', $tenant->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /** @return iterable<string, array{string}> */
    public static function validSubdomains(): iterable
    {
        yield 'una letra' => ['a'];
        yield 'con dígitos' => ['clinica2'];
        yield 'con guion interno' => ['centro-medico-sur'];
        yield '63 caracteres' => [str_repeat('a', 63)];
    }

    #[DataProvider('validSubdomains')]
    public function testAcceptsValidSubdomain(string $subdomain): void
    {
        self::assertSame($subdomain, $this->tenant($subdomain)->getSubdomain());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSubdomains(): iterable
    {
        yield 'vacío' => [''];
        yield 'mayúsculas' => ['Centro'];
        yield 'guion al inicio' => ['-centro'];
        yield 'guion al final' => ['centro-'];
        yield 'con punto' => ['centro.sur'];
        yield 'con espacio' => ['centro sur'];
        yield 'con tilde' => ['clínica'];
        yield 'con guion bajo' => ['centro_sur'];
        yield '64 caracteres' => [str_repeat('a', 64)];
    }

    #[DataProvider('invalidSubdomains')]
    public function testRejectsInvalidSubdomain(string $subdomain): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->tenant($subdomain);
    }

    public function testRejectsBlankName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TenantDbConfig('centro-demo', '   ', 'rce_tenant_demo', 'database', 5432, 'rce_app', new \DateTimeImmutable());
    }

    private function tenant(string $subdomain): TenantDbConfig
    {
        return new TenantDbConfig($subdomain, 'Centro Demo', 'rce_tenant_demo', 'database', 5432, 'rce_app', new \DateTimeImmutable());
    }
}
