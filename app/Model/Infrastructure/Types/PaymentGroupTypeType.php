<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Payment\Group\Type;
use Consistence\Enum\Enum;

final class PaymentGroupTypeType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return Type::class;
    }
}
