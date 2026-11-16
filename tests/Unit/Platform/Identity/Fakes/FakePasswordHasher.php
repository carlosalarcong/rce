<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Identity\Fakes;

use App\Platform\Identity\Application\PasswordHasherInterface;

final class FakePasswordHasher implements PasswordHasherInterface
{
    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return 'hashed:'.hash('sha256', $plainPassword);
    }
}
