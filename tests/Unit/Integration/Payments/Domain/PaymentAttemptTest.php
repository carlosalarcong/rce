<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Payments\Domain;

use App\Integration\Payments\Domain\PaymentAttempt;
use App\Integration\Payments\Domain\PaymentStatus;
use PHPUnit\Framework\TestCase;

final class PaymentAttemptTest extends TestCase
{
    public function testStartsInStartedStatus(): void
    {
        $attempt = new PaymentAttempt('pa-1', 'webpay', 'cobro-123-abc', 15000);

        self::assertSame(PaymentStatus::Started, $attempt->status());
        self::assertSame(15000, $attempt->amount());
    }

    public function testRejectsZeroOrNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentAttempt('pa-1', 'webpay', 'cobro-123-abc', 0);
    }

    public function testRejectsMissingIdempotencyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PaymentAttempt('pa-1', 'webpay', '', 15000);
    }

    public function testRejectsUnknownStatus(): void
    {
        $attempt = new PaymentAttempt('pa-1', 'webpay', 'cobro-123-abc', 15000);

        $this->expectException(\ValueError::class);
        $attempt->setStatus('pagado');
    }

    public function testLabelsAreInSpanishAndFinalStatesAreFinal(): void
    {
        self::assertSame('Pendiente de confirmación', PaymentStatus::PendingConfirmation->label());
        self::assertFalse(PaymentStatus::Started->isFinal());
        self::assertFalse(PaymentStatus::PendingConfirmation->isFinal());
        self::assertTrue(PaymentStatus::Confirmed->isFinal());
        self::assertTrue(PaymentStatus::Rejected->isFinal());
        self::assertTrue(PaymentStatus::Expired->isFinal());
    }
}
