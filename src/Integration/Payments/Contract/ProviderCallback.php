<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Lo que llegó de un proveedor por webhook o retorno del navegador, sin depender del framework. */
final readonly class ProviderCallback
{
    /**
     * @param array<string, string> $headers nombres en minúsculas
     * @param array<string, string> $query
     * @param array<string, string> $form    campos de un POST de formulario (ej.: token_ws de Webpay)
     */
    public function __construct(
        public string $provider,
        public array $headers,
        public array $query,
        public array $form,
        public string $body,
    ) {
    }
}
