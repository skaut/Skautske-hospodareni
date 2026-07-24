<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\PaymentMethod;
use Consistence\Enum\Enum;

final class ChitPaymentMethodType extends AbstractEnumType
{
    public const NAME = 'chit_payment_method';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return PaymentMethod::class;
    }
}
