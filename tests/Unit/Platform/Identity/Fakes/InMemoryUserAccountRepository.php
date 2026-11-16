<?php

declare(strict_types=1);

namespace App\Tests\Unit\Platform\Identity\Fakes;

use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\UserAccount;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;

final class InMemoryUserAccountRepository implements UserAccountRepositoryInterface
{
    /** @var array<string, UserAccount> */
    public array $accounts = [];

    public function byId(string $id): ?UserAccount
    {
        return $this->accounts[$id] ?? null;
    }

    public function byEmail(EmailAddress $email): ?UserAccount
    {
        foreach ($this->accounts as $account) {
            if ($account->email()->equals($email)) {
                return $account;
            }
        }

        return null;
    }

    public function save(UserAccount $account): void
    {
        $this->accounts[$account->id()] = $account;
    }
}
