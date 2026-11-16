<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Messaging;

use App\Platform\Tenancy\Application\ActiveTenantInterface;

final class FakeActiveTenant implements ActiveTenantInterface
{
    /** @var list<string> */
    public array $switches = [];

    /** @param list<string> $broken tenants cuyo cambio falla */
    public function __construct(private ?string $current = null, private readonly array $broken = [])
    {
    }

    public function identifier(): ?string
    {
        return $this->current;
    }

    public function switchTo(string $identifier): void
    {
        if (\in_array($identifier, $this->broken, true)) {
            throw new \RuntimeException('Tenant database down.');
        }
        $this->switches[] = $identifier;
        $this->current = $identifier;
    }
}
