<?php

declare(strict_types=1);

namespace App\Tests\Integration\Integration\Payments;

use App\Integration\Payments\Application\GatewayRegistry;
use App\Integration\Payments\Application\ProviderNotAvailableException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Los adaptadores quedan registrados por su tag, pero inactivos hasta la
 * configuración por tenant. Un adaptador sin registrar diría "No adapter".
 */
final class TransbankAdaptersRegistrationTest extends KernelTestCase
{
    #[DataProvider('adapters')]
    public function testAdapterIsRegisteredButNotActiveYet(string $name): void
    {
        $registry = self::getContainer()->get(GatewayRegistry::class);

        try {
            $registry->paymentGateway($name);
            self::fail('No provider should be active yet.');
        } catch (ProviderNotAvailableException $e) {
            self::assertStringContainsString('not active', $e->getMessage());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function adapters(): iterable
    {
        yield 'webpay' => ['webpay'];
        yield 'transbank_pos' => ['transbank_pos'];
    }
}
