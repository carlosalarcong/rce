<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Tasks;

use App\Integration\Messaging\Application\TaskQueueInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Simulador local de Cloud Tasks: guarda los avisos con su hora en Valkey
 * (sorted set) y el contenedor "tasks" (LocalTaskRunnerCommand) los entrega
 * por HTTP a /_tasks, como lo haría Cloud Tasks. En prod se usa CloudTasksQueue.
 */
#[AsAlias(TaskQueueInterface::class)]
final readonly class LocalTaskQueue implements TaskQueueInterface
{
    public const string KEY = 'rce:tasks:local';

    public function __construct(
        #[Autowire(service: 'rce.redis')]
        private \Redis $redis,
        private string $key = self::KEY,
    ) {
    }

    public function schedule(string $tenant, string $messageId, \DateTimeImmutable $at): void
    {
        // Mismo aviso dos veces = uno solo; si ya estaba, se queda con la hora más temprana (LT).
        $this->redis->zAdd($this->key, ['LT'], (float) $at->format('U.u'), json_encode([$tenant, $messageId], \JSON_THROW_ON_ERROR));
    }

    /**
     * Saca los avisos que ya tocan. Si dos runners compiten, solo uno se lleva cada aviso.
     *
     * @return list<array{string, string}> [tenant, messageId]
     */
    public function takeDue(\DateTimeImmutable $now, int $limit = 50): array
    {
        /** @var list<string> $members */
        $members = $this->redis->zRangeByScore($this->key, '-inf', $now->format('U.u'), ['limit' => [0, $limit]]);

        $due = [];
        foreach ($members as $member) {
            if (1 !== $this->redis->zRem($this->key, $member)) {
                continue;
            }
            $decoded = json_decode($member, true);
            if (\is_array($decoded) && \is_string($decoded[0] ?? null) && \is_string($decoded[1] ?? null)) {
                $due[] = [$decoded[0], $decoded[1]];
            }
        }

        return $due;
    }
}
