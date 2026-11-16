<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Infrastructure\Tasks;

use App\Integration\Messaging\Application\TaskRequestVerifierInterface;
use Google\Auth\AccessToken;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * GCP: Cloud Tasks y Cloud Scheduler firman cada llamada con un token OIDC de
 * la cuenta de servicio configurada. Se acepta solo si Google lo firmó, la
 * audiencia es esta app y el correo es esa cuenta de servicio.
 */
final readonly class GoogleOidcTaskVerifier implements TaskRequestVerifierInterface
{
    private const array ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    /** @var \Closure(string, array<string, mixed>): (array<mixed>|false) */
    private \Closure $verify;

    /**
     * @param (\Closure(string, array<string, mixed>): (array<mixed>|false))|null $verify para tests; por defecto, google/auth
     */
    public function __construct(
        #[Autowire('%env(TASKS_AUDIENCE)%')]
        private string $audience,
        #[Autowire('%env(CLOUD_TASKS_SERVICE_ACCOUNT)%')]
        private string $serviceAccount,
        ?\Closure $verify = null,
    ) {
        $this->verify = $verify ?? static fn (string $token, array $options): array|false => new AccessToken()->verify($token, $options);
    }

    public function isTrusted(?string $authorization): bool
    {
        if (null === $authorization || !str_starts_with($authorization, 'Bearer ') || '' === $this->audience || '' === $this->serviceAccount) {
            return false;
        }

        try {
            $claims = ($this->verify)(substr($authorization, 7), ['audience' => $this->audience]);
        } catch (\Throwable) {
            return false;
        }

        return \is_array($claims)
            && \in_array($claims['iss'] ?? null, self::ISSUERS, true)
            && ($claims['email'] ?? null) === $this->serviceAccount
            && true === ($claims['email_verified'] ?? false);
    }
}
