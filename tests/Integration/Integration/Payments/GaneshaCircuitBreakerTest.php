<?php

declare(strict_types=1);

namespace App\Tests\Integration\Integration\Payments;

use App\Integration\Payments\Infrastructure\CircuitBreaker\GaneshaCircuitBreaker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/** Contra el Valkey real del stack (servicio redis de compose.yaml). */
final class GaneshaCircuitBreakerTest extends TestCase
{
    private const int MIN_REQUESTS = 4;

    private GaneshaCircuitBreaker $breaker;
    private string $provider;

    protected function setUp(): void
    {
        $url = $_SERVER['REDIS_URL'] ?? $_ENV['REDIS_URL'] ?? null;
        self::assertIsString($url);

        $redis = RedisAdapter::createConnection($url);
        self::assertInstanceOf(\Redis::class, $redis);

        $this->breaker = new GaneshaCircuitBreaker($redis, 50, self::MIN_REQUESTS, 30, 60);
        // Un proveedor distinto por prueba: no se pisan entre corridas ni con otros tests.
        $this->provider = 'test_'.bin2hex(random_bytes(6));
    }

    public function testRejectsInvalidThresholds(): void
    {
        $redis = RedisAdapter::createConnection('redis://redis:6379', ['lazy' => true]);
        self::assertInstanceOf(\Redis::class, $redis);

        $this->expectException(\InvalidArgumentException::class);
        new GaneshaCircuitBreaker($redis, 0, 5, 30, 60);
    }

    public function testProviderIsAvailableAtFirst(): void
    {
        self::assertTrue($this->breaker->isAvailable($this->provider));
    }

    public function testTripsWhenMostRecentCallsFailed(): void
    {
        $this->failTimes(self::MIN_REQUESTS);

        self::assertFalse($this->breaker->isAvailable($this->provider));
    }

    public function testDoesNotTripBeforeMinimumRequests(): void
    {
        $this->failTimes(self::MIN_REQUESTS - 1);

        self::assertTrue($this->breaker->isAvailable($this->provider));
    }

    public function testDoesNotTripWhenFailuresAreBelowTheRate(): void
    {
        // 1 falla cada 3 llamadas: 33 %, bajo el 50 %.
        for ($i = 0; $i < self::MIN_REQUESTS * 2; ++$i) {
            $this->breaker->recordFailure($this->provider);
            $this->breaker->recordSuccess($this->provider);
            $this->breaker->recordSuccess($this->provider);
        }

        self::assertTrue($this->breaker->isAvailable($this->provider));
    }

    public function testOneProviderFailingDoesNotAffectAnother(): void
    {
        $this->failTimes(self::MIN_REQUESTS);

        self::assertFalse($this->breaker->isAvailable($this->provider));
        self::assertTrue($this->breaker->isAvailable($this->provider.'_otro'));
    }

    private function failTimes(int $times): void
    {
        for ($i = 0; $i < $times; ++$i) {
            $this->breaker->recordFailure($this->provider);
        }
    }
}
