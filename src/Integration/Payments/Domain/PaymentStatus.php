<?php

declare(strict_types=1);

namespace App\Integration\Payments\Domain;

/**
 * Estados de un intento de cobro. Las transiciones válidas las define la máquina
 * de estados "payment_attempt" (config/packages/workflow.yaml):
 *
 *   started → pending_confirmation → confirmed | rejected | expired
 */
enum PaymentStatus: string
{
    case Started = 'started';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Expired = 'expired';

    /** Texto para la pantalla. */
    public function label(): string
    {
        return match ($this) {
            self::Started => 'Iniciado',
            self::PendingConfirmation => 'Pendiente de confirmación',
            self::Confirmed => 'Confirmado',
            self::Rejected => 'Rechazado',
            self::Expired => 'Expirado',
        };
    }

    public function isFinal(): bool
    {
        return \in_array($this, [self::Confirmed, self::Rejected, self::Expired], true);
    }
}
