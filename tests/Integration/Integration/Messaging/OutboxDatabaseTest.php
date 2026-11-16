<?php

declare(strict_types=1);

namespace App\Tests\Integration\Integration\Messaging;

use App\Integration\Messaging\Application\OutboxStatus;
use App\Integration\Messaging\Infrastructure\Outbox\DbalOutboxStore;
use App\Integration\Messaging\Infrastructure\Outbox\OutboxTransport;
use App\Integration\Messaging\Infrastructure\Outbox\PendingTaskNotifier;
use App\Tests\Unit\Integration\Messaging\FakeActiveTenant;
use App\Tests\Unit\Integration\Messaging\FakeTaskQueue;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Tools\DsnParser;
use DoctrineMigrations\Tenant\Version20261116033456;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

/**
 * Contra PostgreSQL real: una base de prueba con la migración de tenant de la
 * bandeja (la misma que corre en cada clínica).
 */
final class OutboxDatabaseTest extends TestCase
{
    private const string DATABASE = 'rce_test_outbox';

    private static ?Connection $connection = null;

    private Connection $db;
    private DbalOutboxStore $store;
    private MockClock $clock;

    public static function setUpBeforeClass(): void
    {
        $url = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
        self::assertIsString($url);
        $params = new DsnParser(['postgresql' => 'pdo_pgsql'])->parse($url);

        $admin = DriverManager::getConnection(['dbname' => 'postgres'] + $params);
        if (false === $admin->fetchOne('SELECT 1 FROM pg_database WHERE datname = ?', [self::DATABASE])) {
            $admin->executeStatement('CREATE DATABASE '.self::DATABASE);
        }
        $admin->close();

        self::$connection = DriverManager::getConnection(['dbname' => self::DATABASE] + $params);
        self::$connection->executeStatement('DROP TABLE IF EXISTS outbox_message');
        // Las migraciones no se autocargan: se usa la misma que corre en cada clínica.
        require_once \dirname(__DIR__, 4).'/migrations/Tenant/Version20261116033456.php';
        $migration = new Version20261116033456(self::$connection, new NullLogger());
        $migration->up(new Schema());
        /** @var list<\Doctrine\Migrations\Query\Query> $queries */
        $queries = $migration->getSql();
        foreach ($queries as $query) {
            self::$connection->executeStatement($query->getStatement());
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$connection?->close();
    }

    protected function setUp(): void
    {
        self::assertNotNull(self::$connection);
        $this->db = self::$connection;
        $this->db->executeStatement('TRUNCATE outbox_message');
        $this->store = new DbalOutboxStore($this->db);
        $this->clock = new MockClock('2026-11-16 12:00:00 UTC');
    }

    public function testMessageIsUndoneWithTheBusinessTransaction(): void
    {
        $tasks = new FakeTaskQueue();
        $notifier = new PendingTaskNotifier($tasks, $this->clock, new NullLogger());
        $transport = $this->transport('clinica-a', $notifier);

        $this->db->beginTransaction();
        $transport->send(new Envelope(new \stdClass()));
        $this->db->rollBack();

        self::assertEquals(0, $this->db->fetchOne('SELECT count(*) FROM outbox_message'));

        $this->db->beginTransaction();
        $sent = $transport->send(new Envelope(new \stdClass()));
        $this->db->commit();
        $notifier->flush();

        $id = $sent->last(TransportMessageIdStamp::class)?->getId();
        self::assertIsString($id);
        self::assertSame('clinica-a', $this->db->fetchOne('SELECT tenant FROM outbox_message WHERE id = ?', [$id]));
        // El aviso del mensaje deshecho también sale, pero no encontrará nada: solo importa que el confirmado se avise.
        self::assertContains($id, array_column($tasks->scheduled, 1));
    }

    public function testSendingWithoutTenantFails(): void
    {
        $transport = $this->transport(null, new PendingTaskNotifier(new FakeTaskQueue(), $this->clock, new NullLogger()));

        $this->expectException(\LogicException::class);
        $transport->send(new Envelope(new \stdClass()));
    }

    public function testClaimIsAtomicAndCountsAttempts(): void
    {
        $id = $this->add();

        $first = $this->store->claim($id, $this->clock->now());
        self::assertNotNull($first);
        self::assertSame(1, $first->attempts);
        self::assertSame(OutboxStatus::Processing, $first->status);
        self::assertNull($this->store->claim($id, $this->clock->now()), 'A second claim must not take it again.');

        $this->store->markDone($id, $this->clock->now());
        self::assertNull($this->store->claim($id, $this->clock->now()));
    }

    public function testRetryIsNotClaimableBeforeItsTime(): void
    {
        $id = $this->add();
        $this->store->claim($id, $this->clock->now());
        $this->store->markRetry($id, $this->clock->now()->modify('+60 seconds'), \RuntimeException::class);

        self::assertNull($this->store->claim($id, $this->clock->now()));
        $this->clock->sleep(60);
        $record = $this->store->claim($id, $this->clock->now());
        self::assertNotNull($record);
        self::assertSame(2, $record->attempts);
    }

    public function testOverdueFindsLostNotificationsAndDeadWorkers(): void
    {
        $lost = $this->add();
        $dead = $this->add();
        $fresh = $this->add();
        $this->store->claim($dead, $this->clock->now());
        $this->clock->sleep(11 * 60);
        $this->db->update('outbox_message', ['notified_at' => $this->clock->now()->format('Y-m-d H:i:sP')], ['id' => $fresh]);

        $overdue = $this->store->takeOverdue($this->clock->now(), 100);

        sort($overdue);
        $expected = [$lost, $dead];
        sort($expected);
        self::assertSame($expected, $overdue);
        self::assertSame([], $this->store->takeOverdue($this->clock->now(), 100), 'Just notified: not overdue again yet.');
    }

    public function testFailedKeepsOnlyTheErrorClass(): void
    {
        $id = $this->add();
        $this->store->markFailed($id, $this->clock->now(), \RuntimeException::class);

        self::assertSame(['status' => 'failed', 'last_error' => \RuntimeException::class], $this->db->fetchAssociative('SELECT status, last_error FROM outbox_message WHERE id = ?', [$id]));
    }

    public function testStatusIsConstrainedInTheDatabase(): void
    {
        $id = $this->add();

        $this->expectException(\Doctrine\DBAL\Exception::class);
        $this->db->update('outbox_message', ['status' => 'whatever'], ['id' => $id]);
    }

    private function add(): string
    {
        $id = \sprintf('%08x-0000-4000-8000-%012x', random_int(0, 0xFFFFFFFF), random_int(0, 0xFFFFFFFFFFFF));
        $this->store->add($id, 'clinica-a', \stdClass::class, 'body', ['type' => \stdClass::class], $this->clock->now());

        return $id;
    }

    private function transport(?string $tenant, PendingTaskNotifier $notifier): OutboxTransport
    {
        return new OutboxTransport($this->store, new FakeActiveTenant($tenant), $notifier, new PhpSerializer(), $this->clock);
    }
}
