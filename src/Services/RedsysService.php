<?php

declare(strict_types=1);

namespace App\Services;

final class RedsysService
{
    /** @param array<string, mixed> $config */
    public function __construct(private array $config)
    {
    }

    /** @param array<string, mixed> $order */
    public function buildPaymentParams(array $order): array
    {
        return [];
    }

    public function getEndpointUrl(): string
    {
        return 'https://sis-t.redsys.es:25443/sis/realizarPago';
    }

    public function validateNotification(string $signatureVersion, string $merchantParams, string $signature): array
    {
        return [];
    }
}
