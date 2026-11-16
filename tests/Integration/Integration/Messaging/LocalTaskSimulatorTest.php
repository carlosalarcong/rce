<?php

declare(strict_types=1);

namespace App\Tests\Integration\Integration\Messaging;

use App\Integration\Messaging\Infrastructure\Tasks\LocalTaskQueue;
use App\Integration\Messaging\Infrastructure\Tasks\LocalTaskRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/** El simulador de Cloud Tasks contra Valkey real, con una llave propia por test. */
final class LocalTaskSimulatorTest extends TestCase
{
    private \Redis $redis;
    private string $key;
    private LocalTaskQueue $queue;

    protected function setUp(): void
    {
        $url = $_ENV['REDIS_URL'] ?? $_SERVER['REDIS_URL'] ?? 'redis://redis:6379';
        self::assertIsString($url);
        $redis = RedisAdapter::createConnection($url);
        self::assertInstanceOf(\Redis::class, $redis);
        $this->redis = $redis;
        $this->key = 'rce:test:tasks:'.bin2hex(random_bytes(4));
        $this->queue = new LocalTaskQueue($this->redis, $this->key);
    }

    protected function tearDown(): void
    {
        $this->redis->del($this->key);
    }

    public function testDeliversOnlyWhatIsDueOnceAndKeepsTheEarliestTime(): void
    {
        $now = new \DateTimeImmutable('2026-11-16 12:00:00 UTC');
        $this->queue->schedule('clinica-a', 'id-1', $now);
        $this->queue->schedule('clinica-a', 'id-1', $now->modify('+5 minutes'));
        $this->queue->schedule('clinica-b', 'id-2', $now->modify('+1 minute'));

        self::assertSame([['clinica-a', 'id-1']], $this->queue->takeDue($now));
        self::assertSame([], $this->queue->takeDue($now), 'Already taken.');
        self::assertSame([['clinica-b', 'id-2']], $this->queue->takeDue($now->modify('+1 minute')));
    }

    public function testRunnerPostsSignedCallsRetriesFailuresAndSweepsEveryMinute(): void
    {
        $now = new \DateTimeImmutable('2026-11-16 12:00:00 UTC');
        $this->queue->schedule('clinica-a', 'id-ok', $now);
        $this->queue->schedule('clinica-a', 'id-503', $now);

        $calls = [];
        $post = static function (string $url, string $authorization) use (&$calls): int {
            $calls[] = [$url, $authorization];

            return str_contains($url, 'id-503') ? 503 : 200;
        };
        $runner = new LocalTaskRunner($this->queue, new NullLogger(), 'http://nginx/', 'token-local-de-prueba-123', $post);

        $result = $runner->tick($now);

        self::assertSame(['delivered' => 1, 'failed' => 1, 'swept' => true], $result);
        self::assertContains(['http://nginx/_tasks/outbox/clinica-a/id-ok', 'Bearer token-local-de-prueba-123'], $calls);
        self::assertContains(['http://nginx/_tasks/sweep', 'Bearer token-local-de-prueba-123'], $calls);

        // El que falló vuelve en 10 s; la barrida no se repite antes de un minuto.
        self::assertSame(['delivered' => 0, 'failed' => 0, 'swept' => false], $runner->tick($now->modify('+5 seconds')));
        self::assertSame(['delivered' => 0, 'failed' => 1, 'swept' => false], $runner->tick($now->modify('+10 seconds')));
        self::assertTrue($runner->tick($now->modify('+60 seconds'))['swept']);
    }
}
