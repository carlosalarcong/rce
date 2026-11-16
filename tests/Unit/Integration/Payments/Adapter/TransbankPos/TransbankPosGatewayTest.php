<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Adapter\TransbankPos;

use App\Integration\Payments\Adapter\TransbankPos\TransbankPosGateway;
use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmPayment;
use App\Integration\Payments\Contract\IdempotencyKey;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\PaymentResult;
use App\Integration\Payments\Contract\RefundPayment;
use App\Integration\Payments\Contract\StartPayment;
use PHPUnit\Framework\TestCase;

final class TransbankPosGatewayTest extends TestCase
{
    public function testStartWaitsForTheTerminalWithBuyOrderAsTicket(): void
    {
        $started = new TransbankPosGateway()->startPayment(new StartPayment($this->key(), 'BO-2001', Clp::positive(15000), null));

        self::assertSame(PaymentNextStep::WaitForTerminal, $started->nextStep);
        self::assertSame('BO-2001', $started->providerReference);
        self::assertNull($started->redirectUrl);
    }

    public function testApprovedSaleIsConfirmedButMarkedAsNotVerifiedByTransbank(): void
    {
        $outcome = new TransbankPosGateway()->confirm($this->confirm($this->sale()));

        self::assertSame(PaymentResult::Confirmed, $outcome->result);
        self::assertSame('XZ123456', $outcome->authorizationCode);
        self::assertSame(15000, $outcome->amount->value);
        self::assertFalse($outcome->providerResponse['verified_by_provider']);
        self::assertSame(60, $outcome->providerResponse['operation_number']);
        // 15/11/2026 12:00:00 en Santiago (UTC-3 en verano) = 15:00 UTC.
        self::assertEquals(new \DateTimeImmutable('2026-11-15T15:00:00Z'), $outcome->occurredAt);
    }

    public function testCardDataIsNeverKept(): void
    {
        $outcome = new TransbankPosGateway()->confirm($this->confirm($this->sale()));

        self::assertArrayNotHasKey('last4Digits', $outcome->providerResponse);
        self::assertArrayNotHasKey('accountNumber', $outcome->providerResponse);
        $stored = (string) json_encode($outcome->providerResponse);
        self::assertStringNotContainsString('6677', $stored);
        self::assertStringNotContainsString('300000000000000', $stored);
    }

    public function testRejectedSaleIsAResult(): void
    {
        $outcome = new TransbankPosGateway()->confirm($this->confirm($this->sale(['responseCode' => 1, 'responseMessage' => 'Rechazado', 'authorizationCode' => ''])));

        self::assertSame(PaymentResult::Rejected, $outcome->result);
        self::assertNull($outcome->authorizationCode);
        self::assertSame('Rechazado', $outcome->providerResponse['response_message']);
    }

    public function testNoTerminalAnswerYetIsPending(): void
    {
        $outcome = new TransbankPosGateway()->confirm(new ConfirmPayment($this->key(), 'BO-2001', [], Clp::positive(15000)));

        self::assertSame(PaymentResult::Pending, $outcome->result);
    }

    public function testApprovedWithoutAuthorizationCodeIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('authorization code');
        new TransbankPosGateway()->confirm($this->confirm($this->sale(['authorizationCode' => ''])));
    }

    public function testAmountDifferentFromWhatWasChargedIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('differs');
        new TransbankPosGateway()->confirm($this->confirm($this->sale(['amount' => 1500])));
    }

    public function testResponseFromAnotherSaleIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ticket');
        new TransbankPosGateway()->confirm($this->confirm($this->sale(['ticket' => 'BO-9999'])));
    }

    public function testResponseWithoutCodeOrAmountIsRefused(): void
    {
        try {
            new TransbankPosGateway()->confirm($this->confirm($this->sale(['responseCode' => null])));
            self::fail('Expected an exception for a missing response code.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        new TransbankPosGateway()->confirm($this->confirm($this->sale(['amount' => '15.000'])));
    }

    public function testRepeatedResponseGivesTheSameOutcome(): void
    {
        $gateway = new TransbankPosGateway();

        self::assertEquals($gateway->confirm($this->confirm($this->sale())), $gateway->confirm($this->confirm($this->sale())));
    }

    public function testRefundIsMadeOnTheTerminal(): void
    {
        $this->expectException(\LogicException::class);
        new TransbankPosGateway()->refund(new RefundPayment($this->key(), 'BO-2001', Clp::positive(15000)));
    }

    private function key(): IdempotencyKey
    {
        return new IdempotencyKey('attempt-0002');
    }

    /** @param array<string, scalar|null> $terminal */
    private function confirm(array $terminal): ConfirmPayment
    {
        return new ConfirmPayment($this->key(), 'BO-2001', $terminal, Clp::positive(15000));
    }

    /**
     * Respuesta de venta como la entrega la librería JS de Transbank (datos ficticios).
     *
     * @param array<string, scalar|null> $override
     *
     * @return array<string, scalar|null>
     */
    private function sale(array $override = []): array
    {
        return array_merge([
            'functionCode' => 210,
            'responseCode' => 0,
            'responseMessage' => 'Aprobado',
            'commerceCode' => 597000000002,
            'terminalId' => 'TERM0001',
            'ticket' => 'BO-2001',
            'authorizationCode' => 'XZ123456',
            'amount' => 15000,
            'sharesNumber' => 0,
            'last4Digits' => 6677,
            'operationNumber' => 60,
            'cardType' => 'CR',
            'accountingDate' => '15/11/2026 12:00:00',
            'accountNumber' => '300000000000000',
            'cardBrand' => 'VI',
            'realDate' => '15/11/2026 12:00:00',
            'employeeId' => 1,
        ], $override);
    }
}
