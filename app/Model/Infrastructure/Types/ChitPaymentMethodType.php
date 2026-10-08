<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\PaymentMethod;
use Consistence\Enum\Enum;

final class ChitPaymentMethodType extends AbstractEnumType
{
    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return PaymentMethod::class;
    }
}
