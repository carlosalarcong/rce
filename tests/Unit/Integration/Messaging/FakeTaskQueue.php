<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use App\Integration\Messaging\Application\TaskQueueInterface;

final class FakeTaskQueue implements TaskQueueInterface
{
    /** @var list<array{string, string, \DateTimeImmutable}> */
    public array $scheduled = [];

    public function schedule(string $tenant, string $messageId, \DateTimeImmutable $at): void
    {
        $this->scheduled[] = [$tenant, $messageId, $at];
    }

    public function count(): int
    {
        return \count($this->scheduled);
    }
}
