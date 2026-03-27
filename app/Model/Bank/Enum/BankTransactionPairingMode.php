<?php

declare(strict_types=1);

namespace App\Model\Bank\Enum;

enum BankTransactionPairingMode: string
{
    case AUTOMATIC = 'automatic';
    case MANUAL = 'manual';
}
