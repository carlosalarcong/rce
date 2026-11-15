<?php

declare(strict_types=1);

namespace App\Integration\Payments\Application;

final class ProviderNotAvailableException extends \RuntimeException
{
    public static function unknown(string $provider): self
    {
        return new self(\sprintf('No adapter named "%s".', $provider));
    }

    public static function inactive(string $provider): self
    {
        return new self(\sprintf('Provider "%s" is not active for this tenant.', $provider));
    }
}
