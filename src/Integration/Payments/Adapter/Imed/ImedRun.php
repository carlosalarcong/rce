<?php

declare(strict_types=1);

namespace App\Integration\Payments\Adapter\Imed;

/**
 * Formato de RUN que usa IMED: rellenado con ceros a la izquierda hasta 12
 * caracteres ("12345678-9" → "0012345678-9"), como lo envía melisa_prod.
 * Validar con el manual de IMED.
 */
final class ImedRun
{
    private const int LENGTH = 12;

    private const string EMPTY = '0000000000-0';

    public static function format(string $normalizedRun): string
    {
        if (1 !== preg_match('/^\d{1,10}-[\dK]$/', $normalizedRun)) {
            throw new \InvalidArgumentException('RUN must be normalized (digits, dash and check digit).');
        }

        return str_pad($normalizedRun, self::LENGTH, '0', \STR_PAD_LEFT);
    }

    /** IMED pide un RUN en ceros cuando no hay profesional o solicitante. */
    public static function formatOrEmpty(?string $normalizedRun): string
    {
        return null === $normalizedRun ? self::EMPTY : self::format($normalizedRun);
    }
}
