<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use App\Platform\Tenancy\Application\TenantDirectoryInterface;

final readonly class FakeTenantDirectory implements TenantDirectoryInterface
{
    /** @param list<string> $identifiers */
    public function __construct(private array $identifiers)
    {
    }

    public function identifiers(): array
    {
        return $this->identifiers;
    }
}
