<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Security;

use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/** Guarda cuándo entró la persona por última vez. */
final readonly class RecordLastLogin
{
    public function __construct(
        private UserAccountRepositoryInterface $accounts,
        private ClockInterface $clock,
    ) {
    }

    #[AsEventListener]
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof SecurityUser) {
            return;
        }
        $account = $this->accounts->byId($user->getUserIdentifier());
        if (null !== $account) {
            $account->recordLogin($this->clock->now());
            $this->accounts->save($account);
        }
    }
}
