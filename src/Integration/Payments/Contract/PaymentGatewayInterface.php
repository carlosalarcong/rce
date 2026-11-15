<?php

declare(strict_types=1);

namespace App\Integration\Payments\Contract;

/**
 * Cobrar dinero: Webpay, POS de Transbank, Klap, POS manual.
 *
 * Nunca recibe ni devuelve datos de tarjeta: el pago se hace en la plataforma
 * del proveedor. Lo llama un worker, nunca un controller.
 *
 * @throws ProviderUnavailableException en timeout o caída (reintentable)
 */
interface PaymentGatewayInterface extends ProviderInterface
{
    /** Inicia el cobro. Ej.: Webpay crea la transacción y devuelve a dónde redirigir al paciente. */
    public function startPayment(StartPayment $request): PaymentStarted;

    /** Confirma con el proveedor el estado real del cobro (Webpay: commit). Puede seguir pendiente. */
    public function confirm(ConfirmPayment $request): PaymentOutcome;

    /** Devuelve todo o parte de un cobro confirmado. */
    public function refund(RefundPayment $request): RefundOutcome;
}
