<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\Imed;

use App\Integration\Payments\Contract\ProviderUnavailableException;

/** Llama una operación del servicio SOAP de IMED. Existe para poder simular IMED en los tests. */
interface ImedSoapClientInterface
{
    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed> respuesta convertida a arreglos (sin objetos)
     *
     * @throws ProviderUnavailableException si IMED no responde o responde con un fault
     */
    public function call(string $operation, array $params): array;
}
