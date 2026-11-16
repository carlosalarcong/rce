<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Adapter\Webpay;

use App\Integration\Payments\Adapter\Webpay\WebpayGateway;
use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmPayment;
use App\Integration\Payments\Contract\IdempotencyKey;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\PaymentResult;
use App\Integration\Payments\Contract\ProviderCallback;
use App\Integration\Payments\Contract\ProviderCredentials;
use App\Integration\Payments\Contract\ProviderCredentialsInterface;
use App\Integration\Payments\Contract\ProviderNotConfiguredException;
use App\Integration\Payments\Contract\ProviderUnavailableException;
use App\Integration\Payments\Contract\RefundPayment;
use App\Integration\Payments\Contract\StartPayment;
use App\Integration\Payments\Infrastructure\Configuration\NoProviderCredentials;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WebpayGatewayTest extends TestCase
{
    private const string TOKEN = '01ab23cd45ef67ab89cd01ef23ab45cd67ef89ab01cd23ef45ab67cd89ef01ab';

    private FakeTransbankHttpClient $http;

    protected function setUp(): void
    {
        $this->http = new FakeTransbankHttpClient();
    }

    public function testStartCreatesTransactionAndRedirectsWithToken(): void
    {
        $this->http->respond(200, ['token' => self::TOKEN, 'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction']);

        $started = $this->gateway()->startPayment($this->start());

        self::assertSame(PaymentNextStep::Redirect, $started->nextStep);
        self::assertSame(self::TOKEN, $started->providerReference);
        self::assertSame('https://webpay3gint.transbank.cl/webpayserver/initTransaction', $started->redirectUrl);
        self::assertSame(['token_ws' => self::TOKEN], $started->redirectFields);

        $sent = $this->http->requests[0];
        self::assertSame('POST', $sent['method']);
        self::assertStringStartsWith('https://webpay3gint.transbank.cl/', $sent['url']);
        self::assertSame(['buy_order' => 'BO-1001', 'session_id' => 'BO-1001', 'amount' => 15000, 'return_url' => 'https://demo.rce.localhost/webhooks/webpay'], $sent['payload']);
        self::assertIsArray($sent['options']);
        self::assertSame(7, $sent['options']['timeout']);
        self::assertIsArray($sent['options']['headers']);
        self::assertSame('597000000001', $sent['options']['headers']['Tbk-Api-Key-Id']);
    }

    public function testProductionCredentialsUseProductionHost(): void
    {
        $this->http->respond(200, ['token' => self::TOKEN, 'url' => 'https://webpay3g.transbank.cl/webpayserver/initTransaction']);

        $this->gateway(production: true)->startPayment($this->start());

        self::assertStringStartsWith('https://webpay3g.transbank.cl/', $this->http->requests[0]['url']);
    }

    public function testStartNeedsReturnUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->gateway()->startPayment(new StartPayment($this->key(), 'BO-1001', Clp::positive(15000), null));
    }

    public function testNetworkErrorIsProviderUnavailable(): void
    {
        $this->http->fail(new ConnectException('timeout', new Request('POST', 'https://webpay3gint.transbank.cl/')));

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->startPayment($this->start());
    }

    public function testServerErrorIsProviderUnavailable(): void
    {
        $this->http->respond(503, ['error_message' => 'Service unavailable']);

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->startPayment($this->start());
    }

    public function testClientErrorOnCreateIsNotRetryable(): void
    {
        $this->http->respond(401, ['error_message' => 'Not Authorized']);

        try {
            $this->gateway()->startPayment($this->start());
            self::fail('Expected an exception.');
        } catch (\UnexpectedValueException $e) {
            self::assertStringContainsString('HTTP 401', $e->getMessage());
        }
    }

    public function testMissingCredentialsFailBeforeCallingTransbank(): void
    {
        $gateway = new WebpayGateway(new NoProviderCredentials(), 7, $this->http);

        $this->expectException(ProviderNotConfiguredException::class);
        try {
            $gateway->startPayment($this->start());
        } finally {
            self::assertSame([], $this->http->requests);
        }
    }

    public function testConfirmAuthorizedPaymentWithoutKeepingCardData(): void
    {
        $this->http->respond(200, $this->statusBody('AUTHORIZED', 0));

        $outcome = $this->gateway()->confirm($this->confirm(15000));

        self::assertSame(PaymentResult::Confirmed, $outcome->result);
        self::assertSame('1213', $outcome->authorizationCode);
        self::assertSame(15000, $outcome->amount->value);
        self::assertSame(self::TOKEN, $outcome->providerReference);
        self::assertEquals(new \DateTimeImmutable('2026-11-15T15:00:00Z'), $outcome->occurredAt);
        self::assertSame('PUT', $this->http->requests[0]['method']);
        self::assertStringEndsWith('/transactions/'.self::TOKEN, $this->http->requests[0]['url']);

        self::assertArrayNotHasKey('card_detail', $outcome->providerResponse);
        self::assertStringNotContainsString('6623', (string) json_encode($outcome->providerResponse));
    }

    public function testRejectedPaymentIsAResultNotAnException(): void
    {
        $this->http->respond(200, $this->statusBody('FAILED', -1, authorizationCode: '000000'));

        $outcome = $this->gateway()->confirm($this->confirm());

        self::assertSame(PaymentResult::Rejected, $outcome->result);
        self::assertNull($outcome->authorizationCode);
        self::assertSame(-1, $outcome->providerResponse['response_code']);
    }

    public function testRepeatedReturnFallsBackToStatusAndDoesNotChargeTwice(): void
    {
        $this->http
            ->respond(422, ['error_message' => 'Invalid status 1 for transaction while authorizing'])
            ->respond(200, $this->statusBody('AUTHORIZED', 0));

        $outcome = $this->gateway()->confirm($this->confirm());

        self::assertSame(PaymentResult::Confirmed, $outcome->result);
        self::assertSame(['PUT', 'GET'], array_column($this->http->requests, 'method'));
    }

    public function testAbandonedPaymentStaysPending(): void
    {
        $this->http
            ->respond(422, ['error_message' => 'Invalid status 0 for transaction while authorizing'])
            ->respond(200, ['status' => 'INITIALIZED', 'amount' => 15000, 'buy_order' => 'BO-1001']);

        $outcome = $this->gateway()->confirm($this->confirm());

        self::assertSame(PaymentResult::Pending, $outcome->result);
    }

    public function testUnknownStatusIsNeverConfirmed(): void
    {
        $this->http->respond(200, $this->statusBody('SOMETHING_NEW', 0));

        self::assertSame(PaymentResult::Pending, $this->gateway()->confirm($this->confirm())->result);
    }

    public function testAmountDifferentFromExpectedIsRefused(): void
    {
        $this->http->respond(200, $this->statusBody('AUTHORIZED', 0, amount: 1500));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('differs');
        $this->gateway()->confirm($this->confirm(15000));
    }

    public function testCommitTimeoutIsProviderUnavailable(): void
    {
        $this->http->fail(new ConnectException('timeout', new Request('PUT', 'https://webpay3gint.transbank.cl/')));

        $this->expectException(ProviderUnavailableException::class);
        $this->gateway()->confirm($this->confirm());
    }

    public function testRefund(): void
    {
        $this->http->respond(200, ['type' => 'NULLIFIED', 'response_code' => 0, 'authorization_code' => '123456', 'nullified_amount' => 5000, 'balance' => 10000]);

        $outcome = $this->gateway()->refund(new RefundPayment($this->key(), self::TOKEN, Clp::positive(5000)));

        self::assertTrue($outcome->refunded);
        self::assertSame(5000, $outcome->amount->value);
        self::assertSame(['amount' => 5000], $this->http->requests[0]['payload']);
    }

    public function testRefundRejectedByTransbankIsAResult(): void
    {
        $this->http->respond(422, ['error_message' => 'Amount to refund exceeds the balance']);

        $outcome = $this->gateway()->refund(new RefundPayment($this->key(), self::TOKEN, Clp::positive(99999)));

        self::assertFalse($outcome->refunded);
        self::assertSame('Amount to refund exceeds the balance', $outcome->providerResponse['error_message']);
    }

    /** @param array<string, string> $form */
    #[DataProvider('callbacks')]
    public function testCallbackToken(array $form, ?string $expected): void
    {
        $callback = new ProviderCallback('webpay', [], [], $form, '');

        self::assertSame(null !== $expected, $this->gateway()->verifyCallback($callback));
        if (null !== $expected) {
            self::assertSame($expected, $this->gateway()->referenceFrom($callback));
        }
    }

    /** @return iterable<string, array{array<string, string>, ?string}> */
    public static function callbacks(): iterable
    {
        yield 'normal return' => [['token_ws' => self::TOKEN], self::TOKEN];
        yield 'patient cancelled' => [['TBK_TOKEN' => self::TOKEN, 'TBK_ORDEN_COMPRA' => 'BO-1001', 'TBK_ID_SESION' => 'BO-1001'], self::TOKEN];
        yield 'form expired, no token' => [['TBK_ORDEN_COMPRA' => 'BO-1001', 'TBK_ID_SESION' => 'BO-1001'], null];
        yield 'malformed token' => [['token_ws' => "abc'; DROP TABLE x"], null];
    }

    public function testReferenceFromInvalidCallbackFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->gateway()->referenceFrom(new ProviderCallback('webpay', [], [], [], ''));
    }

    private function gateway(bool $production = false): WebpayGateway
    {
        $credentials = new class($production) implements ProviderCredentialsInterface {
            public function __construct(private bool $production)
            {
            }

            public function for(string $provider): ProviderCredentials
            {
                return new ProviderCredentials($this->production, ['commerce_code' => '597000000001', 'api_key' => 'test-secret']);
            }
        };

        return new WebpayGateway($credentials, 7, $this->http);
    }

    private function key(): IdempotencyKey
    {
        return new IdempotencyKey('attempt-0001');
    }

    private function start(): StartPayment
    {
        return new StartPayment($this->key(), 'BO-1001', Clp::positive(15000), 'https://demo.rce.localhost/webhooks/webpay');
    }

    private function confirm(?int $expected = null): ConfirmPayment
    {
        return new ConfirmPayment($this->key(), self::TOKEN, [], null === $expected ? null : Clp::positive($expected));
    }

    /** @return array<string, mixed> */
    private function statusBody(string $status, int $responseCode, int $amount = 15000, string $authorizationCode = '1213'): array
    {
        return [
            'vci' => 'TSY',
            'amount' => $amount,
            'status' => $status,
            'buy_order' => 'BO-1001',
            'session_id' => 'BO-1001',
            'card_detail' => ['card_number' => '6623'],
            'accounting_date' => '1115',
            'transaction_date' => '2026-11-15T15:00:00.000Z',
            'authorization_code' => $authorizationCode,
            'payment_type_code' => 'VN',
            'response_code' => $responseCode,
            'installments_number' => 0,
        ];
    }
}
