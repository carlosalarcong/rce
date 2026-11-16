<?php

declare(strict_types=1);

namespace App\Integration\Payments\Infrastructure\Configuration;

use App\Integration\Payments\Contract\ProviderCredentials;
use App\Integration\Payments\Contract\ProviderCredentialsInterface;
use App\Integration\Payments\Contract\ProviderNotConfiguredException;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;

/**
 * Solo dev y test (config/services.yaml): credenciales PÚBLICAS de los
 * ambientes de prueba de los proveedores. Nunca apuntan a producción.
 *
 * Webpay: las que publica Transbank para su ambiente de integración.
 */
final readonly class SandboxProviderCredentials implements ProviderCredentialsInterface
{
    public function for(string $provider): ProviderCredentials
    {
        return match ($provider) {
            'webpay' => new ProviderCredentials(false, [
                'commerce_code' => WebpayPlus::INTEGRATION_COMMERCE_CODE,
                'api_key' => Options::INTEGRATION_API_KEY,
            ]),
            default => throw ProviderNotConfiguredException::forProvider($provider),
        };
    }
}
