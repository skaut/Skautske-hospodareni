<?php

declare(strict_types=1);

namespace App\Model\Invoice\Enum;

enum InvoicePaymentType: string
{
    case CASH = 'V hotovosti';
    case CARD = 'Kartou';
    case TRANSFER = 'Převodem';

    /** @return array<string> */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> */
    public static function toSelect(): array
    {
        return [
            self::CARD->name => self::CARD->value,
            self::TRANSFER->name => self::TRANSFER->value,
            self::CASH->name => self::CASH->value,
        ];
    }
}
