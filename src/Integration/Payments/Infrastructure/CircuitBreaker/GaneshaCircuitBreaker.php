<?php

declare(strict_types=1);

namespace App\Integration\Payments\Infrastructure\CircuitBreaker;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis as RedisAdapter;
use App\Integration\Payments\Application\CircuitBreakerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Circuit breaker sobre Ganesha con el estado en Valkey (Memorystore en GCP),
 * compartido por todos los workers. Es por proveedor, no por tenant: si el
 * proveedor se cae, se cae para todos.
 *
 * Estrategia por tasa (la única que soporta el adaptador Redis de Ganesha): si
 * en la ventana de tiempo hubo al menos $minimumRequests llamadas y fallaron
 * $failureRatePercent % o más, se corta durante $pauseSeconds segundos.
 */
#[AsAlias(CircuitBreakerInterface::class)]
final readonly class GaneshaCircuitBreaker implements CircuitBreakerInterface
{
    private const string KEY_PREFIX = 'rce:circuit:';

    private Ganesha $ganesha;

    public function __construct(
        #[Autowire(service: 'rce.redis')]
        \Redis $redis,
        #[Autowire('%env(int:CIRCUIT_BREAKER_FAILURE_RATE)%')]
        int $failureRatePercent,
        #[Autowire('%env(int:CIRCUIT_BREAKER_MIN_REQUESTS)%')]
        int $minimumRequests,
        #[Autowire('%env(int:CIRCUIT_BREAKER_WINDOW_SECONDS)%')]
        int $windowSeconds,
        #[Autowire('%env(int:CIRCUIT_BREAKER_PAUSE_SECONDS)%')]
        int $pauseSeconds,
    ) {
        if ($failureRatePercent < 1 || $failureRatePercent > 100) {
            throw new \InvalidArgumentException(\sprintf('Circuit breaker failure rate must be 1-100, got %d.', $failureRatePercent));
        }
        if ($minimumRequests < 1 || $windowSeconds < 1 || $pauseSeconds < 1) {
            throw new \InvalidArgumentException('Circuit breaker minimum requests, window and pause must be at least 1.');
        }

        $this->ganesha = Builder::withRateStrategy()
            ->adapter(new RedisAdapter($redis))
            ->failureRateThreshold($failureRatePercent)
            ->minimumRequests($minimumRequests)
            ->timeWindow($windowSeconds)
            ->intervalToHalfOpen($pauseSeconds)
            ->build();
    }

    public function isAvailable(string $provider): bool
    {
        return $this->ganesha->isAvailable(self::KEY_PREFIX.$provider);
    }

    public function recordSuccess(string $provider): void
    {
        $this->ganesha->success(self::KEY_PREFIX.$provider);
    }

    public function recordFailure(string $provider): void
    {
        $this->ganesha->failure(self::KEY_PREFIX.$provider);
    }
}
