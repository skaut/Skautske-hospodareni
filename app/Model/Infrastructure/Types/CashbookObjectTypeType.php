<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\ObjectType;
use Consistence\Enum\Enum;

final class CashbookObjectTypeType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return ObjectType::class;
    }
}
