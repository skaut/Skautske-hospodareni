<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\CashbookType;
use Consistence\Enum\Enum;

final class CashbookTypeType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return CashbookType::class;
    }
}
