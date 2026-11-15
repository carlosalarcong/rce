<?php

declare(strict_types=1);

namespace App\Tests\Integration\Integration\Payments;

use App\Integration\Payments\Domain\PaymentAttempt;
use App\Integration\Payments\Domain\PaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

final class PaymentAttemptWorkflowTest extends KernelTestCase
{
    public function testHappyPathToConfirmed(): void
    {
        $attempt = $this->newAttempt();

        $this->workflow()->apply($attempt, 'request_confirmation');
        self::assertSame(PaymentStatus::PendingConfirmation, $attempt->status());

        $this->workflow()->apply($attempt, 'confirm');
        self::assertSame(PaymentStatus::Confirmed, $attempt->status());
    }

    /** @return iterable<string, array{string, PaymentStatus}> */
    public static function endings(): iterable
    {
        yield 'confirmado' => ['confirm', PaymentStatus::Confirmed];
        yield 'rechazado' => ['reject', PaymentStatus::Rejected];
        yield 'expirado' => ['expire', PaymentStatus::Expired];
    }

    #[DataProvider('endings')]
    public function testPendingEndsInAnyFinalState(string $transition, PaymentStatus $expected): void
    {
        $attempt = $this->newAttempt();
        $this->workflow()->apply($attempt, 'request_confirmation');

        $this->workflow()->apply($attempt, $transition);

        self::assertSame($expected, $attempt->status());
        self::assertTrue($expected->isFinal());
    }

    /** @return iterable<string, array{string}> */
    public static function skips(): iterable
    {
        yield 'confirmar sin pasar por pendiente' => ['confirm'];
        yield 'rechazar sin pasar por pendiente' => ['reject'];
        yield 'expirar sin pasar por pendiente' => ['expire'];
    }

    #[DataProvider('skips')]
    public function testCannotSkipPendingConfirmation(string $transition): void
    {
        $attempt = $this->newAttempt();

        self::assertFalse($this->workflow()->can($attempt, $transition));
        $this->expectException(NotEnabledTransitionException::class);
        $this->workflow()->apply($attempt, $transition);
    }

    #[DataProvider('endings')]
    public function testFinalStatesHaveNoWayOut(string $transition, PaymentStatus $final): void
    {
        $attempt = $this->newAttempt();
        $this->workflow()->apply($attempt, 'request_confirmation');
        $this->workflow()->apply($attempt, $transition);

        self::assertSame($final, $attempt->status());
        self::assertSame([], $this->workflow()->getEnabledTransitions($attempt));
    }

    public function testCannotRequestConfirmationTwice(): void
    {
        $attempt = $this->newAttempt();
        $this->workflow()->apply($attempt, 'request_confirmation');

        self::assertFalse($this->workflow()->can($attempt, 'request_confirmation'));
    }

    private function newAttempt(): PaymentAttempt
    {
        return new PaymentAttempt('pa-1', 'webpay', 'cobro-123-abc', 15000);
    }

    private function workflow(): WorkflowInterface
    {
        return self::getContainer()->get('state_machine.payment_attempt');
    }
}
