<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Datos públicos de cuenta para transferencia (no son secretos).
 */
final class BankTransferDetails
{
    public const HOLDER = 'Assoc de Amigos del Yoga y la Botanica';

    public const IBAN = 'ES96 0081 0037 9100 0226 4328';

    public const BIC = 'BSABESBB';

    /**
     * Texto exacto que el cliente debe poner en el concepto de la transferencia.
     */
    public static function transferConcept(string $orderRef): string
    {
        return 'DONATIVO ' . trim($orderRef);
    }
}
