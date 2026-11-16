<?php

declare(strict_types=1);

namespace App\Platform\Identity\Domain;

interface UserAccountRepositoryInterface
{
    public function byId(string $id): ?UserAccount;

    public function byEmail(EmailAddress $email): ?UserAccount;

    public function save(UserAccount $account): void;
}
