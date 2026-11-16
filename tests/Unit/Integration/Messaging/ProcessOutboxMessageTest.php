<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use App\Integration\Messaging\Application\OutboxRetryPolicy;
use App\Integration\Messaging\Application\OutboxStatus;
use App\Integration\Messaging\Application\ProcessOutboxMessage;
use App\Integration\Messaging\Application\ProcessOutcome;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class ProcessOutboxMessageTest extends TestCase
{
    private const string ID = '0b8f6c2e-1f5a-4c3e-9d7a-2b1c3d4e5f60';

    private FakeActiveTenant $tenant;
    private InMemoryOutboxStore $store;
    private FakeTaskQueue $tasks;
    private MockClock $clock;
    private RecordingBus $bus;
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->tenant = new FakeActiveTenant();
        $this->store = new InMemoryOutboxStore();
        $this->tasks = new FakeTaskQueue();
        $this->clock = new MockClock('2026-11-16 12:00:00 UTC');
        $this->bus = new RecordingBus();
        $this->logger = new RecordingLogger();
    }

    public function testProcessesInItsTenantAndMarksDone(): void
    {
        $this->addMessage('clinica-a');

        self::assertSame(ProcessOutcome::Done, $this->process('clinica-a'));
        self::assertSame(['clinica-a'], $this->tenant->switches);
        self::assertSame(OutboxStatus::Done, $this->store->status(self::ID));
        self::assertCount(1, $this->bus->dispatched);
        self::assertInstanceOf(ReceivedStamp::class, $this->bus->dispatched[0]->last(ReceivedStamp::class));
    }

    public function testRepeatedNotificationDoesNotProcessTwice(): void
    {
        $this->addMessage('clinica-a');

        $this->process('clinica-a');

        self::assertSame(ProcessOutcome::NothingToDo, $this->process('clinica-a'));
        self::assertCount(1, $this->bus->dispatched);
    }

    public function testMessageFromRolledBackTransactionIsIgnored(): void
    {
        self::assertSame(ProcessOutcome::NothingToDo, $this->process('clinica-a'));
        self::assertSame([], $this->bus->dispatched);
    }

    public function testNeverProcessedInAnotherTenant(): void
    {
        $this->addMessage('clinica-a');

        self::assertSame(ProcessOutcome::Failed, $this->process('clinica-b'));
        self::assertSame([], $this->bus->dispatched);
        self::assertSame(OutboxStatus::Failed, $this->store->status(self::ID));
        self::assertSame('critical', $this->logger->logs[0][0]);
    }

    public function testRetriesImmediatelyThenAfterOneAndFiveMinutes(): void
    {
        $this->addMessage('clinica-a');
        $this->bus->failWith(new \RuntimeException('Proveedor caído'));
        $start = $this->clock->now();

        $expected = [0, 60, 300];
        foreach ($expected as $delay) {
            self::assertSame(ProcessOutcome::RetryScheduled, $this->process('clinica-a'));
            $scheduled = end($this->tasks->scheduled);
            self::assertNotFalse($scheduled);
            self::assertEquals($this->clock->now()->modify("+{$delay} seconds"), $scheduled[2]);
            $this->clock->sleep($delay);
        }

        self::assertSame(ProcessOutcome::Failed, $this->process('clinica-a'));
        self::assertSame(OutboxStatus::Failed, $this->store->status(self::ID));
        self::assertGreaterThan($start, $this->clock->now());
        self::assertSame('critical', end($this->logger->logs)[0] ?? null);
    }

    public function testErrorMessageIsNeverStoredOnlyItsClass(): void
    {
        $this->addMessage('clinica-a');
        $this->bus->failWith(new \RuntimeException('Paciente Juan Pérez 11.111.111-1'));

        $this->process('clinica-a');

        self::assertSame(\RuntimeException::class, $this->store->error(self::ID));
        self::assertStringNotContainsString('Pérez', (string) json_encode($this->logger->logs));
    }

    public function testUnrecoverableErrorFailsWithoutRetry(): void
    {
        $this->addMessage('clinica-a');
        $this->bus->failWith(new UnrecoverableMessageHandlingException('No tiene sentido reintentar'));

        self::assertSame(ProcessOutcome::Failed, $this->process('clinica-a'));
        self::assertSame([], $this->tasks->scheduled);
    }

    public function testNotYetDueRetryIsNotTakenEarly(): void
    {
        $this->addMessage('clinica-a');
        $this->bus->failWith(new \RuntimeException('x'));
        $this->process('clinica-a');
        $this->process('clinica-a'); // reintento inmediato falla: el siguiente es en 60 s

        self::assertSame(ProcessOutcome::NothingToDo, $this->process('clinica-a'));
    }

    public function testRetryPolicyLimits(): void
    {
        $policy = new OutboxRetryPolicy();
        $now = new \DateTimeImmutable('2026-11-16 12:00:00 UTC');

        self::assertSame(4, $policy->maxAttempts());
        self::assertEquals($now, $policy->nextAttemptAt(1, $now));
        self::assertNull($policy->nextAttemptAt(4, $now));

        $this->expectException(\InvalidArgumentException::class);
        $policy->nextAttemptAt(0, $now);
    }

    private function process(string $tenant): ProcessOutcome
    {
        $logger = new RecordingLogger();
        $this->logger = $logger;

        return new ProcessOutboxMessage($this->tenant, $this->store, $this->bus, new PhpSerializer(), $this->tasks, new OutboxRetryPolicy(), $this->clock, $logger)($tenant, self::ID);
    }

    private function addMessage(string $tenant): void
    {
        $encoded = new PhpSerializer()->encode(new Envelope(new \stdClass()));
        $this->store->add(self::ID, $tenant, \stdClass::class, $encoded['body'], $encoded['headers'] ?? [], $this->clock->now());
    }
}

final class RecordingBus implements MessageBusInterface
{
    /** @var list<Envelope> */
    public array $dispatched = [];

    private ?\Throwable $failure = null;

    public function failWith(\Throwable $failure): void
    {
        $this->failure = $failure;
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if (null !== $this->failure) {
            throw $this->failure;
        }
        $envelope = Envelope::wrap($message, $stamps);
        $this->dispatched[] = $envelope;

        return $envelope;
    }
}
