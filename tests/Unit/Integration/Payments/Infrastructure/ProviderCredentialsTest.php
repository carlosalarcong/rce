<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Infrastructure;

use App\Integration\Payments\Contract\ProviderCredentials;
use App\Integration\Payments\Contract\ProviderNotConfiguredException;
use App\Integration\Payments\Infrastructure\Configuration\NoProviderCredentials;
use App\Integration\Payments\Infrastructure\Configuration\SandboxProviderCredentials;
use PHPUnit\Framework\TestCase;

final class ProviderCredentialsTest extends TestCase
{
    public function testProductionHasNoCredentialsUntilTenantConfigurationExists(): void
    {
        $this->expectException(ProviderNotConfiguredException::class);
        new NoProviderCredentials()->for('webpay');
    }

    public function testSandboxGivesWebpayIntegrationCredentialsNeverProduction(): void
    {
        $credentials = new SandboxProviderCredentials()->for('webpay');

        self::assertFalse($credentials->production);
        self::assertSame('597055555532', $credentials->get('commerce_code'));
        self::assertNotSame('', $credentials->get('api_key'));
    }

    public function testSandboxHasNothingForOtherProviders(): void
    {
        $this->expectException(ProviderNotConfiguredException::class);
        new SandboxProviderCredentials()->for('klap');
    }

    public function testMissingValueFails(): void
    {
        $this->expectException(ProviderNotConfiguredException::class);
        new ProviderCredentials(false, ['commerce_code' => '1'])->get('api_key');
    }

    public function testValuesNeverLeakInDumpsOrSerialization(): void
    {
        $credentials = new ProviderCredentials(true, ['api_key' => 'super-secret-value']);

        self::assertStringNotContainsString('super-secret-value', print_r($credentials, true));
        self::assertStringNotContainsString('super-secret-value', var_export($credentials->__debugInfo(), true));

        $this->expectException(\LogicException::class);
        serialize($credentials);
    }
}
