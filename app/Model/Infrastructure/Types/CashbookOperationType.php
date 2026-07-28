<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Operation;
use Consistence\Enum\Enum;

final class CashbookOperationType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return Operation::class;
    }
}
