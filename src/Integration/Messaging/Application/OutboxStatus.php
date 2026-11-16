<?php

declare(strict_types=1);

namespace App\Integration\Messaging\Application;

enum OutboxStatus: string
{
    /** Esperando su turno (nuevo o con reintento programado). */
    case Pending = 'pending';
    /** Un proceso lo tomó y lo está haciendo. */
    case Processing = 'processing';
    case Done = 'done';
    /** Agotó los reintentos o no es reintentable: queda para el visor de mensajes. */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'En proceso',
            self::Done => 'Hecho',
            self::Failed => 'Fallido',
        };
    }
}
