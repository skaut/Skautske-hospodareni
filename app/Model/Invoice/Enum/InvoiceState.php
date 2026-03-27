<?php

declare(strict_types=1);

namespace App\Model\Invoice\Enum;

use function array_column;

enum InvoiceState: string
{
    public const ISSUED = 'issued';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    /** @return array<string> */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
