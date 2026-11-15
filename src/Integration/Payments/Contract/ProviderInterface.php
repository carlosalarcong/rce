<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/** Todo adaptador de proveedor tiene un nombre único: registro, configuración del tenant y ruta del webhook. */
interface ProviderInterface
{
    /** Ej.: "webpay", "transbank_pos", "imed", "manual_pos". Minúsculas, dígitos y "_". */
    public function name(): string;
}
