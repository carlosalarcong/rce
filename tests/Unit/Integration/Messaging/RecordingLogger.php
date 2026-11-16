<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use Psr\Log\AbstractLogger;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{string, string, array<mixed>}> */
    public array $logs = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [\is_scalar($level) ? (string) $level : 'unknown', (string) $message, $context];
    }
}
