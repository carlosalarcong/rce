<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\Imed;

/** Crea el cliente SOAP para el WSDL de IMED del centro. */
interface ImedSoapClientFactoryInterface
{
    public function create(string $wsdlUrl): ImedSoapClientInterface;
}
