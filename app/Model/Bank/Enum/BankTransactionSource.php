<?php

declare(strict_types=1);

namespace App\Model\Bank\Enum;

enum BankTransactionSource: string
{
    case FIO = 'fio';
    case GPC = 'gpc';

    public function label(): string
    {
        return match ($this) {
            self::FIO => 'FIO API',
            self::GPC => 'GPC soubor',
        };
    }

    /** @return array<string, string> */
    public static function toSelect(): array
    {
        return [
            self::FIO->value => self::FIO->label(),
            self::GPC->value => self::GPC->label(),
        ];
    }
}
