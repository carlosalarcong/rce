<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Credenciales de un proveedor para el tenant actual (ej.: Webpay: código de
 * comercio y API key). Nunca se imprimen: no van a logs, errores ni dumps.
 */
final readonly class ProviderCredentials
{
    /** @var array<string, string> */
    private array $values;

    /**
     * @param bool                  $production true: ambiente de producción del proveedor; false: ambiente de pruebas
     * @param array<string, string> $values
     */
    public function __construct(
        public bool $production,
        #[\SensitiveParameter] array $values,
    ) {
        $this->values = $values;
    }

    /** @throws ProviderNotConfiguredException si falta el valor */
    public function get(string $key): string
    {
        $value = $this->values[$key] ?? '';
        if ('' === $value) {
            throw new ProviderNotConfiguredException(\sprintf('Missing credential "%s".', $key));
        }

        return $value;
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return ['production' => $this->production, 'values' => '*** hidden ***'];
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        throw new \LogicException('Provider credentials cannot be serialized.');
    }
}
