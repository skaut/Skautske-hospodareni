<?php

declare(strict_types=1);

namespace App\Model\Event\Enum;

use function array_column;

enum CampState: string
{
    case APPROVED_PARENT = 'approvedParent';

    case REAL = 'real';

    case APPROVED_LEADER = 'approvedLeader';

    case DRAFT = 'draft';

    /**
     * Vrátí všechny možné stavy jako pole.
     *
     * @return array<string>
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
