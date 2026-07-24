<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Payment\Group\Type;
use Consistence\Enum\Enum;

final class PaymentGroupTypeType extends AbstractEnumType
{
    public const NAME = 'payment_group_type';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return Type::class;
    }
}
