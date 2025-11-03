<?php

declare(strict_types=1);

namespace Enum;

use function array_column;

enum InvoiceSequenceState: string
{
    public const OPEN = 'open';
    public const CLOSED = 'closed';

    /** @return array<string> */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
