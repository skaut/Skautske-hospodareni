<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Payment\Payment\State;
use Consistence\Enum\Enum;

final class PaymentStateType extends AbstractEnumType
{
    public const NAME = 'payment_state';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @return class-string<Enum> */
    protected function enumClass(): string
    {
        return State::class;
    }
}
