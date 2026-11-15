<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Bonos de atención: IMED, Snabb/BonoWeb, folio manual.
 *
 * Flujo típico (IMED): validate() pregunta a la previsión si el paciente tiene
 * cobertura y cuánto copaga; issueVoucher() emite el bono; cancel() lo anula.
 *
 * @throws ProviderUnavailableException en timeout o caída (reintentable)
 */
interface VoucherProviderInterface extends ProviderInterface
{
    /** Cobertura y copago, sin emitir nada. En el folio manual, valida el folio digitado. */
    public function validate(ValidateVoucher $request): VoucherValidation;

    public function issueVoucher(IssueVoucher $request): VoucherIssued;

    public function cancel(CancelVoucher $request): VoucherCancelled;
}
