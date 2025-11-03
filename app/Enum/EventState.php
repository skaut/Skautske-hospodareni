<?php

declare(strict_types=1);

namespace Enum;

use function array_column;

enum EventState: string
{
    case CLOSED    = 'closed';
    case CANCELLED = 'cancelled';
    case DRAFT     = 'draft';

    /**
     * Vrátí všechny možné stavy jako pole
     *
     * @return array<string>
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
