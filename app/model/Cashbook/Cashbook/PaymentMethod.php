<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Consistence\Enum\Enum;

final class PaymentMethod extends Enum
{
    public const CASH = 'cash';

    public const BANK_TRANSFER = 'bank_transfer';
}
