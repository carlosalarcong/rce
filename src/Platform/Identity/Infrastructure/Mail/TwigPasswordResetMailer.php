<?php

declare(strict_types=1);

namespace App\Platform\Identity\Infrastructure\Mail;

use App\Platform\Identity\Application\PasswordResetMailerInterface;
use App\Platform\Identity\Domain\UserAccount;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(PasswordResetMailerInterface::class)]
final readonly class TwigPasswordResetMailer implements PasswordResetMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urls,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $from,
    ) {
    }

    public function send(UserAccount $account, #[\SensitiveParameter] string $plainToken, \DateTimeImmutable $expiresAt): void
    {
        $link = $this->urls->generate('identity_reset_password', ['token' => $plainToken], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->mailer->send(
            new TemplatedEmail()
                ->from(new Address($this->from, 'RCE'))
                ->to(new Address($account->email()->value, $account->displayName()))
                ->subject('Elige una contraseña nueva para tu cuenta del RCE')
                ->htmlTemplate('identity/email/password_reset.html.twig')
                ->textTemplate('identity/email/password_reset.txt.twig')
                ->context([
                    'name' => $account->displayName(),
                    'link' => $link,
                    'expires_in_minutes' => 30,
                ]),
        );
    }
}
