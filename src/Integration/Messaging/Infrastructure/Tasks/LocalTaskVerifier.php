<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Tasks;

use App\Integration\Messaging\Application\TaskRequestVerifierInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Local: el simulador manda "Bearer <TASKS_LOCAL_TOKEN>". En prod se usa GoogleOidcTaskVerifier. */
#[AsAlias(TaskRequestVerifierInterface::class)]
final readonly class LocalTaskVerifier implements TaskRequestVerifierInterface
{
    public function __construct(
        #[Autowire('%env(TASKS_LOCAL_TOKEN)%')]
        #[\SensitiveParameter]
        private string $token,
    ) {
        if (\strlen($token) < 16) {
            throw new \InvalidArgumentException('TASKS_LOCAL_TOKEN must have at least 16 characters.');
        }
    }

    public function isTrusted(?string $authorization): bool
    {
        return null !== $authorization && hash_equals('Bearer '.$this->token, $authorization);
    }
}
