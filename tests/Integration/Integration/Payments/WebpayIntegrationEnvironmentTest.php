<?php

declare(strict_types=1);

namespace App\Tests\Integration\Integration\Payments;

use App\Integration\Payments\Adapter\Webpay\WebpayGateway;
use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmPayment;
use App\Integration\Payments\Contract\IdempotencyKey;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\PaymentResult;
use App\Integration\Payments\Contract\StartPayment;
use App\Integration\Payments\Infrastructure\Configuration\SandboxProviderCredentials;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contra el ambiente de integración REAL de Transbank (internet). No corre en
 * `make check` ni en la CI: `make test-external`.
 */
#[Group('external')]
final class WebpayIntegrationEnvironmentTest extends TestCase
{
    public function testCreateAndQueryAnUnpaidTransaction(): void
    {
        $gateway = new WebpayGateway(new SandboxProviderCredentials(), 15);
        $buyOrder = 'RCE-'.bin2hex(random_bytes(6));
        $key = new IdempotencyKey('ext-'.$buyOrder);

        $started = $gateway->startPayment(new StartPayment($key, $buyOrder, Clp::positive(1000), 'https://rce.localhost/webhooks/webpay'));

        self::assertSame(PaymentNextStep::Redirect, $started->nextStep);
        self::assertStringStartsWith('https://webpay3gint.transbank.cl/', (string) $started->redirectUrl);

        // Nadie pagó: el commit se rechaza (422) y el estado real es "pendiente", nunca confirmado.
        $outcome = $gateway->confirm(new ConfirmPayment($key, $started->providerReference, [], Clp::positive(1000)));
        self::assertNotSame(PaymentResult::Confirmed, $outcome->result);
    }
}
