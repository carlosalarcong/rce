<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Application;

use App\Integration\Payments\Application\ActiveProvidersInterface;
use App\Integration\Payments\Application\GatewayRegistry;
use App\Integration\Payments\Application\ProviderNotAvailableException;
use App\Integration\Payments\Contract\PaymentGatewayInterface;
use App\Integration\Payments\Contract\VoucherProviderInterface;
use PHPUnit\Framework\TestCase;

final class GatewayRegistryTest extends TestCase
{
    public function testReturnsActiveAdapterByName(): void
    {
        $webpay = $this->gateway('webpay');
        $registry = new GatewayRegistry($this->active('webpay'), [$webpay, $this->gateway('manual_pos')]);

        self::assertSame($webpay, $registry->paymentGateway('webpay'));
    }

    public function testRefusesAdapterNotActiveForTenant(): void
    {
        $registry = new GatewayRegistry($this->active(), [$this->gateway('webpay')]);

        $this->expectException(ProviderNotAvailableException::class);
        $this->expectExceptionMessage('not active');
        $registry->paymentGateway('webpay');
    }

    public function testRefusesUnknownAdapterEvenIfActive(): void
    {
        $registry = new GatewayRegistry($this->active('klap'), [$this->gateway('webpay')]);

        $this->expectException(ProviderNotAvailableException::class);
        $this->expectExceptionMessage('No adapter');
        $registry->paymentGateway('klap');
    }

    public function testAdapterOfOtherTypeIsNotFound(): void
    {
        $registry = new GatewayRegistry($this->active('imed'), [], [$this->voucher('imed')]);

        self::assertSame('imed', $registry->voucherProvider('imed')->name());

        $this->expectException(ProviderNotAvailableException::class);
        $registry->paymentGateway('imed');
    }

    public function testRefusesTwoAdaptersWithTheSameName(): void
    {
        $this->expectException(\LogicException::class);
        new GatewayRegistry($this->active(), [$this->gateway('webpay'), $this->gateway('webpay')]);
    }

    public function testRefusesInvalidAdapterName(): void
    {
        $this->expectException(\LogicException::class);
        new GatewayRegistry($this->active(), [$this->gateway('Web Pay')]);
    }

    private function active(string ...$names): ActiveProvidersInterface
    {
        return new readonly class(array_values($names)) implements ActiveProvidersInterface {
            /** @param list<string> $names */
            public function __construct(private array $names)
            {
            }

            public function isActive(string $provider): bool
            {
                return \in_array($provider, $this->names, true);
            }
        };
    }

    private function gateway(string $name): PaymentGatewayInterface
    {
        $gateway = $this->createStub(PaymentGatewayInterface::class);
        $gateway->method('name')->willReturn($name);

        return $gateway;
    }

    private function voucher(string $name): VoucherProviderInterface
    {
        $provider = $this->createStub(VoucherProviderInterface::class);
        $provider->method('name')->willReturn($name);

        return $provider;
    }
}
