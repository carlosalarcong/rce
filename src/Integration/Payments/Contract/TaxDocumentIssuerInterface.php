<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Boletas electrónicas (SII) a través de un facturador electrónico.
 * Hoy solo existe el contrato: el adaptador se agrega cuando se elija el emisor.
 *
 * @throws ProviderUnavailableException en timeout o caída (reintentable)
 */
interface TaxDocumentIssuerInterface extends ProviderInterface
{
    public function issue(IssueTaxDocument $request): TaxDocumentIssued;

    /** Anula una boleta emitida (según el emisor, emitiendo una nota de crédito). */
    public function void(VoidTaxDocument $request): TaxDocumentVoided;
}
