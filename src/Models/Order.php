<?php

declare(strict_types=1);

namespace App\Models;

final class Order
{
    public static function markAsPaid(string $orderRef, array $params): void
    {
    }

    public static function markAsFailed(string $orderRef, int $code): void
    {
    }
}
