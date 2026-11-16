<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Bonos de atención: IMED, Snabb/BonoWeb, folio manual.
 *
 * Mismo esquema que los pagos web: se inicia la venta, el paciente se identifica
 * y paga el copago en la ventana del proveedor, y después se consulta el resultado,
 * que puede seguir pendiente o traer uno o varios bonos (folios).
 *
 * Los montos y folios salen SOLO de la respuesta del proveedor, nunca del navegador.
 *
 * @throws ProviderUnavailableException en timeout o caída (reintentable)
 */
interface VoucherProviderInterface extends ProviderInterface
{
    /** Inicia la venta. Si el proveedor la rechaza de entrada (ej.: prestación no válida), vuelve con el motivo. */
    public function startVoucherSale(StartVoucherSale $request): VoucherSaleStarted;

    /** Consulta el resultado: pendiente, rechazada o emitida con sus bonos. Sin efectos: se puede repetir. */
    public function confirmVoucherSale(ConfirmVoucherSale $request): VoucherSaleOutcome;

    /** Anula un bono. Si el proveedor no lo anula, vuelve cancelled = false: nunca se da por anulado. */
    public function cancel(CancelVoucher $request): VoucherCancelled;
}
