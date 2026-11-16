<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Contract;

use App\Integration\Payments\Contract\CancelVoucher;
use App\Integration\Payments\Contract\Clp;
use App\Integration\Payments\Contract\ConfirmPayment;
use App\Integration\Payments\Contract\IdempotencyKey;
use App\Integration\Payments\Contract\IssuedVoucher;
use App\Integration\Payments\Contract\IssueTaxDocument;
use App\Integration\Payments\Contract\PaymentNextStep;
use App\Integration\Payments\Contract\PaymentOutcome;
use App\Integration\Payments\Contract\PaymentResult;
use App\Integration\Payments\Contract\PaymentStarted;
use App\Integration\Payments\Contract\RefundPayment;
use App\Integration\Payments\Contract\StartPayment;
use App\Integration\Payments\Contract\StartVoucherSale;
use App\Integration\Payments\Contract\TaxDocumentIssued;
use App\Integration\Payments\Contract\TaxDocumentLine;
use App\Integration\Payments\Contract\TaxDocumentType;
use App\Integration\Payments\Contract\VoidTaxDocument;
use App\Integration\Payments\Contract\VoucherCancelled;
use App\Integration\Payments\Contract\VoucherLine;
use App\Integration\Payments\Contract\VoucherSaleOutcome;
use App\Integration\Payments\Contract\VoucherSaleResult;
use App\Integration\Payments\Contract\VoucherSaleStarted;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContractValueObjectsTest extends TestCase
{
    public function testClpAcceptsZeroButNotNegative(): void
    {
        self::assertSame(0, (new Clp(0))->value);

        $this->expectException(\InvalidArgumentException::class);
        new Clp(-1);
    }

    public function testPositiveClpRejectsZero(): void
    {
        self::assertSame(15000, Clp::positive(15000)->value);

        $this->expectException(\InvalidArgumentException::class);
        Clp::positive(0);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidKeys(): iterable
    {
        yield 'vacía' => [''];
        yield 'muy corta' => ['abc'];
        yield 'muy larga' => [str_repeat('a', 65)];
        yield 'con espacio' => ['abc def ghi'];
        yield 'con tilde' => ['cobro-sesión-1'];
    }

    #[DataProvider('invalidKeys')]
    public function testRejectsInvalidIdempotencyKey(string $key): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IdempotencyKey($key);
    }

    public function testSameIdempotencyKeyIsEqual(): void
    {
        self::assertTrue((new IdempotencyKey('cobro-123-abc'))->equals(new IdempotencyKey('cobro-123-abc')));
        self::assertFalse((new IdempotencyKey('cobro-123-abc'))->equals(new IdempotencyKey('cobro-123-abd')));
    }

    public function testStartPaymentRejectsZeroAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StartPayment($this->key(), 'OC-1', new Clp(0), 'https://centro.rce.cl/pago/retorno');
    }

    /** @return iterable<string, array{string}> */
    public static function invalidBuyOrders(): iterable
    {
        yield 'vacía' => [''];
        yield '27 caracteres (límite de Webpay: 26)' => [str_repeat('A', 27)];
        yield 'con espacio' => ['OC 1'];
    }

    #[DataProvider('invalidBuyOrders')]
    public function testStartPaymentRejectsInvalidBuyOrder(string $buyOrder): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StartPayment($this->key(), $buyOrder, Clp::positive(1000), null);
    }

    public function testStartPaymentAcceptsNullReturnUrlForTerminals(): void
    {
        self::assertNull((new StartPayment($this->key(), str_repeat('A', 26), Clp::positive(1000), null))->returnUrl);
    }

    public function testRedirectStepNeedsUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentStarted('token-1', PaymentNextStep::Redirect);
    }

    public function testTerminalStepDoesNotNeedUrl(): void
    {
        self::assertNull((new PaymentStarted('pos-1', PaymentNextStep::WaitForTerminal))->redirectUrl);
    }

    public function testPaymentStartedNeedsReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentStarted('', PaymentNextStep::None);
    }

    public function testConfirmedPaymentNeedsAuthorizationCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentOutcome(PaymentResult::Confirmed, 'token-1', Clp::positive(1000));
    }

    public function testRejectedOrPendingPaymentDoNotNeedAuthorizationCode(): void
    {
        self::assertNull((new PaymentOutcome(PaymentResult::Rejected, 'token-1', Clp::positive(1000)))->authorizationCode);
        self::assertNull((new PaymentOutcome(PaymentResult::Pending, 'token-1', Clp::positive(1000)))->authorizationCode);
    }

    public function testConfirmAndRefundNeedReference(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ConfirmPayment($this->key(), '');
    }

    public function testRefundRejectsZeroAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RefundPayment($this->key(), 'token-1', new Clp(0));
    }

    public function testVoucherSaleTotalMultipliesQuantity(): void
    {
        $sale = $this->voucherSale([
            new VoucherLine('0101001', 1, new Clp(12000)),
            new VoucherLine('0305001', 2, new Clp(3500)),
        ]);

        self::assertSame(19000, $sale->total()->value);
    }

    public function testVoucherSaleNeedsLines(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->voucherSale([]);
    }

    public function testVoucherSaleNeedsNormalizedRuns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StartVoucherSale($this->key(), '1', '11.111.111-1', '22222222-2', [new VoucherLine('0101001', 1, new Clp(12000))], 'https://x.test/r');
    }

    public function testVoucherLineNeedsQuantityAndNumericItem(): void
    {
        try {
            new VoucherLine('0101001', 0, new Clp(12000));
            self::fail('Expected an exception for quantity 0.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        new VoucherLine('0101001', 1, new Clp(12000), 'X');
    }

    public function testRejectedVoucherSaleNeedsReasonAndNoNextStep(): void
    {
        self::assertTrue(VoucherSaleStarted::rejected('Sin cobertura')->isRejected());

        $this->expectException(\InvalidArgumentException::class);
        new VoucherSaleStarted('ref', PaymentNextStep::Redirect, 'https://x.test', [], 'Sin cobertura');
    }

    public function testIssuedVoucherSaleNeedsVouchersAndOnlyIssuedHasThem(): void
    {
        try {
            new VoucherSaleOutcome(VoucherSaleResult::Issued, 'ref');
            self::fail('Expected an exception for an issued sale without vouchers.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        new VoucherSaleOutcome(VoucherSaleResult::Pending, 'ref', [new IssuedVoucher('F-1', new Clp(1000), [])]);
    }

    public function testFailedCancellationNeedsReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new VoucherCancelled(false, 'F-1');
    }

    public function testCancelVoucherNeedsReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CancelVoucher($this->key(), 'F-1', ' ', '1', '22222222-2');
    }

    /** @param list<VoucherLine> $lines */
    private function voucherSale(array $lines): StartVoucherSale
    {
        return new StartVoucherSale($this->key(), '1', '11111111-1', '22222222-2', $lines, 'https://x.test/r');
    }

    public function testTaxDocumentTotal(): void
    {
        $doc = new IssueTaxDocument($this->key(), TaxDocumentType::ExemptReceipt, [
            new TaxDocumentLine('Consulta médica', 1, new Clp(25000)),
            new TaxDocumentLine('Curación', 2, new Clp(4000)),
        ], new \DateTimeImmutable());

        self::assertSame(33000, $doc->total()->value);
        self::assertSame(41, $doc->type->value);
    }

    public function testTaxDocumentNeedsLines(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IssueTaxDocument($this->key(), TaxDocumentType::ExemptReceipt, [], new \DateTimeImmutable());
    }

    public function testTaxDocumentFolioMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TaxDocumentIssued(TaxDocumentType::ExemptReceipt, 0, new Clp(1000));
    }

    public function testVoidTaxDocumentNeedsReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new VoidTaxDocument($this->key(), TaxDocumentType::ExemptReceipt, 10, '');
    }

    private function key(): IdempotencyKey
    {
        return new IdempotencyKey('cobro-123-abc');
    }
}
