<?php

declare(strict_types=1);

namespace App\Platform\Identity\Application;

use App\Platform\Identity\Domain\EmailAddress;
use App\Platform\Identity\Domain\UserAccount;
use App\Platform\Identity\Domain\UserAccountRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

/** Crea una cuenta con su contraseña. Hoy la usa el comando; después, soporte y las invitaciones. */
final readonly class CreateAccount
{
    public function __construct(
        private UserAccountRepositoryInterface $accounts,
        private PasswordHasherInterface $hasher,
        private PasswordPolicy $policy,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return string el UUID de la cuenta
     *
     * @throws EmailAlreadyRegisteredException
     * @throws WeakPasswordException
     * @throws \InvalidArgumentException       correo o nombre inválidos
     */
    public function __invoke(string $email, string $displayName, #[\SensitiveParameter] string $plainPassword): string
    {
        $address = new EmailAddress($email);
        if (null !== $this->accounts->byEmail($address)) {
            throw new EmailAlreadyRegisteredException('Ya existe una cuenta con ese correo.');
        }
        $this->policy->assertAcceptable($plainPassword, $address->value);

        $account = UserAccount::create(Uuid::v7()->toRfc4122(), $address, $displayName, $this->hasher->hash($plainPassword), $this->clock->now());
        $this->accounts->save($account);

        return $account->id();
    }
}
